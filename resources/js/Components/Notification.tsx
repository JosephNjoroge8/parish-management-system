import React, { useState, useEffect } from 'react';
import { CheckCircle, XCircle, AlertTriangle, Info, X } from 'lucide-react';

export interface NotificationProps {
    id?: string;
    type: 'success' | 'error' | 'warning' | 'info';
    title: string;
    message: string;
    duration?: number;
    onClose?: () => void;
    actions?: Array<{
        label: string;
        onClick: () => void;
        variant?: 'primary' | 'secondary';
    }>;
}

interface NotificationManagerProps {
    notifications: NotificationProps[];
    onRemove: (id: string) => void;
}

const Notification: React.FC<NotificationProps & { onRemove: () => void }> = ({
    type,
    title,
    message,
    duration = 5000,
    onClose,
    onRemove,
    actions = []
}) => {
    const [isVisible, setIsVisible] = useState(true);
    const [isExiting, setIsExiting] = useState(false);

    useEffect(() => {
        if (duration > 0) {
            const timer = setTimeout(() => {
                handleClose();
            }, duration);
            return () => clearTimeout(timer);
        }
    }, [duration]);

    const handleClose = () => {
        if (isExiting) return; // Prevent multiple close calls
        setIsExiting(true);
        setTimeout(() => {
            setIsVisible(false);
            setTimeout(() => {
                onRemove();
                onClose?.();
            }, 100);
        }, 300);
    };

    const getIcon = () => {
        switch (type) {
            case 'success':
                return <CheckCircle className="w-5 h-5 text-green-500" />;
            case 'error':
                return <XCircle className="w-5 h-5 text-red-500" />;
            case 'warning':
                return <AlertTriangle className="w-5 h-5 text-yellow-500" />;
            case 'info':
            default:
                return <Info className="w-5 h-5 text-blue-500" />;
        }
    };

    const getBorderColor = () => {
        switch (type) {
            case 'success':
                return 'border-l-green-500 bg-green-50';
            case 'error':
                return 'border-l-red-500 bg-red-50';
            case 'warning':
                return 'border-l-yellow-500 bg-yellow-50';
            case 'info':
            default:
                return 'border-l-blue-500 bg-blue-50';
        }
    };

    if (!isVisible) return null;

    return (
        <div
            className={`
                fixed top-4 right-4 max-w-sm w-full z-50 
                transform transition-all duration-300 ease-in-out
                ${isExiting ? 'translate-x-full opacity-0' : 'translate-x-0 opacity-100'}
                bg-white rounded-lg shadow-lg border-l-4 p-4
                ${getBorderColor()}
            `}
        >
            <div className="flex items-start space-x-3">
                <div className="flex-shrink-0">
                    {getIcon()}
                </div>
                <div className="flex-1 min-w-0">
                    <h4 className="text-sm font-medium text-gray-900 mb-1">
                        {title}
                    </h4>
                    <p className="text-sm text-gray-700">
                        {message}
                    </p>
                    
                    {actions.length > 0 && (
                        <div className="flex space-x-2 mt-3">
                            {actions.map((action, index) => (
                                <button
                                    key={index}
                                    onClick={action.onClick}
                                    className={`
                                        px-3 py-1 text-xs font-medium rounded transition-colors
                                        ${action.variant === 'primary' 
                                            ? 'bg-blue-600 text-white hover:bg-blue-700' 
                                            : 'bg-gray-200 text-gray-800 hover:bg-gray-300'
                                        }
                                    `}
                                >
                                    {action.label}
                                </button>
                            ))}
                        </div>
                    )}
                </div>
                <button
                    onClick={handleClose}
                    className="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors"
                >
                    <X className="w-4 h-4" />
                </button>
            </div>
        </div>
    );
};

const NotificationManager: React.FC<NotificationManagerProps> = ({
    notifications,
    onRemove
}) => {
    return (
        <div className="fixed top-4 right-4 z-50 space-y-2 pointer-events-none">
            {notifications.map((notification, index) => (
                <div
                    key={notification.id || `notification-${index}`}
                    className="pointer-events-auto"
                >
                    <Notification
                        {...notification}
                        onRemove={() => onRemove(notification.id || index.toString())}
                    />
                </div>
            ))}
        </div>
    );
};

// Global notification state management
class NotificationService {
    private notifications: NotificationProps[] = [];
    private listeners: Array<(notifications: NotificationProps[]) => void> = [];

    subscribe(listener: (notifications: NotificationProps[]) => void) {
        this.listeners.push(listener);
        return () => {
            this.listeners = this.listeners.filter(l => l !== listener);
        };
    }

    private emit() {
        this.listeners.forEach(listener => listener([...this.notifications]));
    }

    add(notification: Omit<NotificationProps, 'id'>) {
        const id = `notification-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        const newNotification = { ...notification, id };
        this.notifications.push(newNotification);
        this.emit();

        // Auto-remove after duration
        if (notification.duration !== 0) {
            setTimeout(() => {
                this.remove(id);
            }, notification.duration || 5000);
        }

        return id;
    }

    remove(id: string) {
        const index = this.notifications.findIndex(n => n.id === id);
        if (index > -1) {
            this.notifications.splice(index, 1);
            this.emit();
        }
    }

    clear() {
        this.notifications = [];
        this.emit();
    }

    // Convenience methods
    success(title: string, message: string, options?: Partial<NotificationProps>) {
        return this.add({ ...options, type: 'success', title, message });
    }

    error(title: string, message: string, options?: Partial<NotificationProps>) {
        return this.add({ ...options, type: 'error', title, message });
    }

    warning(title: string, message: string, options?: Partial<NotificationProps>) {
        return this.add({ ...options, type: 'warning', title, message });
    }

    info(title: string, message: string, options?: Partial<NotificationProps>) {
        return this.add({ ...options, type: 'info', title, message });
    }
}

// Global instance
export const notificationService = new NotificationService();

// React hook for using notifications
export const useNotifications = () => {
    const [notifications, setNotifications] = useState<NotificationProps[]>([]);

    useEffect(() => {
        const unsubscribe = notificationService.subscribe(setNotifications);
        return unsubscribe;
    }, []);

    return {
        notifications,
        add: notificationService.add.bind(notificationService),
        remove: notificationService.remove.bind(notificationService),
        clear: notificationService.clear.bind(notificationService),
        success: notificationService.success.bind(notificationService),
        error: notificationService.error.bind(notificationService),
        warning: notificationService.warning.bind(notificationService),
        info: notificationService.info.bind(notificationService),
    };
};

// Provider component for notifications
export const NotificationProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const { notifications, remove } = useNotifications();

    return (
        <>
            {children}
            <NotificationManager notifications={notifications} onRemove={remove} />
        </>
    );
};

export default Notification;