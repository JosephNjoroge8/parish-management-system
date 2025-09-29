// Notification utility for better UX
export type NotificationType = 'success' | 'error' | 'warning' | 'info';

let currentNotification: HTMLElement | null = null;

export function showNotification(
    title: string, 
    message: string, 
    type: NotificationType = 'info', 
    duration: number = 5000
): void {
    // Remove existing notification
    dismissNotification();
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `
        fixed top-4 right-4 max-w-sm w-full z-50 transform transition-all duration-300 ease-in-out
        bg-white rounded-lg shadow-lg border-l-4 p-4 flex items-start space-x-3
        ${getTypeClasses(type)}
    `;
    
    // Add icon based on type
    const icon = getIcon(type);
    
    notification.innerHTML = `
        <div class="flex-shrink-0">
            ${icon}
        </div>
        <div class="flex-1 min-w-0">
            <h4 class="text-sm font-medium text-gray-900">${title}</h4>
            <p class="text-sm text-gray-700 mt-1">${message}</p>
        </div>
        <button class="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors" onclick="this.parentElement.remove()">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    `;
    
    // Add to DOM with animation
    document.body.appendChild(notification);
    currentNotification = notification;
    
    // Trigger animation
    requestAnimationFrame(() => {
        notification.classList.add('translate-x-0');
        notification.classList.remove('translate-x-full');
    });
    
    // Auto-dismiss if duration is specified
    if (duration > 0) {
        setTimeout(() => {
            dismissNotification();
        }, duration);
    }
}

export function dismissNotification(): void {
    if (currentNotification && currentNotification.parentNode) {
        currentNotification.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => {
            if (currentNotification && currentNotification.parentNode) {
                currentNotification.remove();
            }
            currentNotification = null;
        }, 300);
    }
}

function getTypeClasses(type: NotificationType): string {
    switch (type) {
        case 'success':
            return 'border-green-500 bg-green-50';
        case 'error':
            return 'border-red-500 bg-red-50';
        case 'warning':
            return 'border-yellow-500 bg-yellow-50';
        case 'info':
        default:
            return 'border-blue-500 bg-blue-50';
    }
}

function getIcon(type: NotificationType): string {
    switch (type) {
        case 'success':
            return `
                <div class="flex items-center justify-center w-8 h-8 bg-green-100 rounded-full">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            `;
        case 'error':
            return `
                <div class="flex items-center justify-center w-8 h-8 bg-red-100 rounded-full">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            `;
        case 'warning':
            return `
                <div class="flex items-center justify-center w-8 h-8 bg-yellow-100 rounded-full">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
            `;
        case 'info':
        default:
            return `
                <div class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            `;
    }
}

// Progress notification for longer operations
export function showProgressNotification(title: string, message: string): void {
    showNotification(title, message, 'info', 0); // Don't auto-dismiss
    
    // Add spinning loader to current notification
    if (currentNotification) {
        const icon = currentNotification.querySelector('.flex-shrink-0');
        if (icon) {
            icon.innerHTML = `
                <div class="flex items-center justify-center w-8 h-8">
                    <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
                </div>
            `;
        }
    }
}

// Success notification with celebration effect
export function showSuccessNotification(title: string, message: string): void {
    showNotification(title, message, 'success', 4000);
    
    // Add confetti-like effect (optional enhancement)
    if (currentNotification) {
        currentNotification.classList.add('animate-bounce');
        setTimeout(() => {
            if (currentNotification) {
                currentNotification.classList.remove('animate-bounce');
            }
        }, 1000);
    }
}