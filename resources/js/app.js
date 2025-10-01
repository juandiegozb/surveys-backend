import './bootstrap';

document.addEventListener('DOMContentLoaded', function() {
    // Configuring toast notifications
    window.showToast = function(message, type = 'success') {
        const container = document.getElementById('toast-container') || createToastContainer();
        const toast = document.createElement('div');

        toast.className = `mb-2 px-4 py-2 rounded-md shadow-lg transition-all duration-300 ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-blue-500 text-white'
        }`;

        toast.textContent = message;
        container.appendChild(toast);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 5000);
    };

    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed top-4 right-4 z-50';
        document.body.appendChild(container);
        return container;
    }
});

// Listen to Livewire events
document.addEventListener('livewire:init', () => {
    Livewire.on('toast', (data) => {
        const message = data[0]?.message || data.message || 'Notification';
        const type = data[0]?.type || data.type || 'success';
        window.showToast(message, type);
    });

    Livewire.on('survey-submitted', () => {
        window.showToast('Survey submitted successfully!', 'success');
    });
});
