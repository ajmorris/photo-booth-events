/**
 * Photo Booth frontend JavaScript.
 *
 * @package VirtualPhotoBooth
 */

(function() {
	'use strict';

	// Don't run in Gutenberg editor.
	if ( window.wp && window.wp.domReady ) {
		return;
	}

	/**
	 * Initialize photo booth functionality.
	 */
	function initPhotoBooth() {
		const containers = document.querySelectorAll('.pbe-photo-booth-container');
		
		containers.forEach(container => {
			const blockId = container.dataset.blockId;
			if (!blockId) return;

			const configEl = document.getElementById(blockId + '-config');
			if (!configEl) return;

			let config;
			try {
				config = JSON.parse(configEl.textContent);
			} catch (e) {
				console.error('Failed to parse photo booth config:', e);
				return;
			}

			const photoBooth = new PhotoBoothInstance(blockId, config);
			photoBooth.init();
		});
	}

	/**
	 * Photo Booth instance class.
	 */
	class PhotoBoothInstance {
		constructor(blockId, config) {
			this.blockId = blockId;
			this.config = config;
			this.video = document.getElementById(blockId + '-video');
			this.canvas = document.getElementById(blockId + '-canvas');
			this.captureBtn = document.getElementById(blockId + '-capture');
			this.retakeBtn = document.getElementById(blockId + '-retake');
			this.uploadBtn = document.getElementById(blockId + '-upload');
			this.statusEl = document.getElementById(blockId + '-status');
			
			this.stream = null;
			this.capturedImage = null;
			this.frameImage = null;
		}

		/**
		 * Initialize the photo booth.
		 */
		async init() {
			// Load frame image if provided.
			if (this.config.frameImageUrl) {
				this.frameImage = await this.loadImage(this.config.frameImageUrl);
			}

			// Set up event listeners.
			this.captureBtn.addEventListener('click', () => this.capture());
			this.retakeBtn.addEventListener('click', () => this.retake());
			this.uploadBtn.addEventListener('click', () => this.upload());

			// Initialize camera.
			this.initCamera();
		}

		/**
		 * Initialize camera.
		 */
		async initCamera() {
			try {
				this.stream = await navigator.mediaDevices.getUserMedia({
					video: {
						facingMode: 'user',
						width: { ideal: 1280 },
						height: { ideal: 720 }
					},
					audio: false
				});

				if (this.video) {
					this.video.srcObject = this.stream;
					this.video.play();
				}
			} catch (error) {
				this.showError(this.getErrorMessage(error));
			}
		}

		/**
		 * Get user-friendly error message.
		 */
		getErrorMessage(error) {
			if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
				return 'Camera permission denied. Please allow camera access and refresh the page.';
			} else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
				return 'No camera found. Please connect a camera and refresh the page.';
			} else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
				return 'Camera is already in use by another application.';
			} else if (error.name === 'OverconstrainedError' || error.name === 'ConstraintNotSatisfiedError') {
				return 'Camera constraints not satisfied. Please try a different camera.';
			} else {
				return 'Failed to access camera: ' + error.message;
			}
		}

		/**
		 * Load image from URL.
		 */
		loadImage(url) {
			return new Promise((resolve, reject) => {
				const img = new Image();
				img.crossOrigin = 'anonymous';
				img.onload = () => resolve(img);
				img.onerror = reject;
				img.src = url;
			});
		}

		/**
		 * Capture photo.
		 */
		capture() {
			if (!this.video || !this.canvas) return;

			const videoWidth = this.video.videoWidth;
			const videoHeight = this.video.videoHeight;

			if (videoWidth === 0 || videoHeight === 0) {
				this.showError('Video not ready. Please wait a moment and try again.');
				return;
			}

			// Set canvas dimensions.
			this.canvas.width = videoWidth;
			this.canvas.height = videoHeight;

			// Draw video frame to canvas.
			const ctx = this.canvas.getContext('2d');
			ctx.drawImage(this.video, 0, 0, videoWidth, videoHeight);

			// Draw frame overlay if available.
			if (this.frameImage) {
				ctx.drawImage(this.frameImage, 0, 0, videoWidth, videoHeight);
			}

			// Convert to blob.
			this.canvas.toBlob((blob) => {
				if (blob) {
					this.capturedImage = blob;
					this.showCaptured();
				} else {
					this.showError('Failed to capture image.');
				}
			}, 'image/jpeg', 0.9);
		}

		/**
		 * Show captured image.
		 */
		showCaptured() {
			if (!this.video || !this.canvas) return;

			// Hide video, show canvas.
			this.video.style.display = 'none';
			this.canvas.style.display = 'block';

			// Update button visibility.
			this.captureBtn.style.display = 'none';
			this.retakeBtn.style.display = 'inline-block';
			this.uploadBtn.style.display = 'inline-block';

			this.clearStatus();
		}

		/**
		 * Retake photo.
		 */
		retake() {
			if (!this.video || !this.canvas) return;

			// Show video, hide canvas.
			this.video.style.display = 'block';
			this.canvas.style.display = 'none';

			// Update button visibility.
			this.captureBtn.style.display = 'inline-block';
			this.retakeBtn.style.display = 'none';
			this.uploadBtn.style.display = 'none';

			this.capturedImage = null;
			this.clearStatus();
		}

		/**
		 * Upload photo.
		 */
		async upload() {
			if (!this.capturedImage || !window.pbeData) {
				this.showError('Upload data not available.');
				return;
			}

			this.uploadBtn.disabled = true;
			this.showStatus('Uploading...', 'info');

			const formData = new FormData();
			formData.append('action', 'pbe_upload_photo');
			formData.append('nonce', window.pbeData.nonce);
			formData.append('event_id', this.config.eventId);
			formData.append('photo', this.capturedImage, 'photo.jpg');

			try {
				const response = await fetch(window.pbeData.ajaxUrl, {
					method: 'POST',
					body: formData
				});

				const data = await response.json();

				if (data.success) {
					this.showStatus('Photo uploaded successfully!', 'success');
					
					// Reset after a delay.
					setTimeout(() => {
						this.retake();
					}, 2000);
				} else {
					this.showError(data.data?.message || 'Upload failed.');
				}
			} catch (error) {
				this.showError('Network error: ' + error.message);
			} finally {
				this.uploadBtn.disabled = false;
			}
		}

		/**
		 * Show status message.
		 */
		showStatus(message, type = 'info') {
			if (!this.statusEl) return;
			
			this.statusEl.textContent = message;
			this.statusEl.className = 'pbe-status ' + (type === 'error' ? 'error' : type === 'success' ? 'success' : '');
		}

		/**
		 * Show error message.
		 */
		showError(message) {
			this.showStatus(message, 'error');
		}

		/**
		 * Clear status message.
		 */
		clearStatus() {
			if (this.statusEl) {
				this.statusEl.textContent = '';
				this.statusEl.className = 'pbe-status';
			}
		}

		/**
		 * Cleanup.
		 */
		destroy() {
			if (this.stream) {
				this.stream.getTracks().forEach(track => track.stop());
			}
		}
	}

	// Initialize when DOM is ready.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initPhotoBooth);
	} else {
		initPhotoBooth();
	}
})();


