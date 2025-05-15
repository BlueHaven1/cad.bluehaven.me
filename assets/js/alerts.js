// Global variables for alert sound management
let alertSoundInterval = null;
let alertSound = null; // Will be initialized when DOM is loaded

// Function to play alert sound
function playAlertSound() {
  if (alertSound) {
    alertSound.currentTime = 0; // Reset to beginning
    alertSound.play().catch(e => console.error('Error playing sound:', e));
  }
}

// Function to start repeating alert sound
function startAlertSound() {
  // Clear any existing interval first
  stopAlertSound();

  // Play immediately
  playAlertSound();

  // Set up interval to play every 10 seconds
  alertSoundInterval = setInterval(playAlertSound, 10000);
}

// Function to stop alert sound
function stopAlertSound() {
  if (alertSoundInterval) {
    clearInterval(alertSoundInterval);
    alertSoundInterval = null;
  }
}

// Function to toggle alerts (for dispatcher use)
function toggleAlert(type) {
  fetch('../includes/toggle-alert.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `type=${encodeURIComponent(type)}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      loadAlerts(); // Refresh banner if successful
    } else {
      alert('Failed to toggle alert.');
    }
  });
}

// Function to load and check alerts
function loadAlerts() {
  fetch('../includes/get-alerts.php')
    .then(res => res.json())
    .then(alerts => {
      const banner = document.getElementById('alert-banner');
      const spacer = document.getElementById('alert-spacer');

      const active = alerts.filter(a => a.status);
      if (active.length === 0) {
        banner.classList.add('hidden');
        spacer.classList.add('h-0');
        stopAlertSound(); // Stop sound when no active alerts
        return;
      }

      const messages = active.map(alert => {
        return alert.type === 'signal100'
          ? '🚨 SIGNAL 100 IS IN EFFECT'
          : '🔇 10-3 RADIO SILENCE IN EFFECT';
      });

      banner.textContent = messages.join(' | ');
      banner.className = 'w-full text-center text-white text-lg font-bold py-3 fixed top-0 z-50 bg-red-600';
      spacer.className = 'h-14'; // reserve space for banner

      // If we have active alerts, make sure sound is playing
      if (!alertSoundInterval) {
        startAlertSound();
      }
    });
}

// Initialize alerts system
function initializeAlerts() {
  // Initialize audio element reference
  alertSound = document.getElementById('alert-sound');

  // Check if there are active alerts on page load
  loadAlerts();

  // Set up interval to check alerts every 5 seconds
  setInterval(loadAlerts, 5000);
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', initializeAlerts);
