import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { notifications } from '@mantine/notifications';
import { IconCheck, IconX, IconInfoCircle } from '@tabler/icons-react';
import { useTheme } from '../theme';

/**
 * FlashMessages Component
 *
 * Listens to Inertia page props for 'flash' messages and displays
 * themed notifications using Mantine Notifications system.
 */
export default function FlashMessages() {
    const { props } = usePage();
    const { flash } = props;
    const { colorConfig } = useTheme();

    useEffect(() => {
        if (flash?.success) {
            notifications.show({
                title: 'Success',
                message: flash.success,
                color: 'green',
                icon: <IconCheck size="1.1rem" />,
                autoClose: 3000,
                withBorder: true,
                styles: {
                    root: {
                        borderColor: colorConfig.primary,
                        backgroundColor: '#f0fdf4', // Light green bg, or maybe use theme logic
                    },
                    title: {
                        color: colorConfig.primary,
                        fontWeight: 600
                    },
                    description: {
                        color: '#1f2937' // Dark gray
                    },
                    icon: {
                        backgroundColor: 'transparent',
                        color: colorConfig.primary
                    }
                }
            });
        }

        if (flash?.error) {
             notifications.show({
                title: 'Error',
                message: flash.error,
                color: 'red',
                icon: <IconX size="1.1rem" />,
                autoClose: 5000,
                withBorder: true,
                styles: {
                    root: {
                        borderColor: '#ef4444',
                        backgroundColor: '#fef2f2',
                    },
                    title: {
                        color: '#b91c1c', // Dark red
                        fontWeight: 600
                    },
                    icon: {
                        backgroundColor: 'transparent',
                        color: '#ef4444'
                    }
                }
            });
        }

        if (flash?.message) {
             notifications.show({
                title: 'Notification',
                message: flash.message,
                color: 'blue',
                icon: <IconInfoCircle size="1.1rem" />,
                autoClose: 4000,
                withBorder: true,
                styles: {
                    root: {
                        borderColor: colorConfig.secondary,
                    },
                    title: {
                        color: colorConfig.secondary,
                        fontWeight: 600
                    },
                    icon: {
                        backgroundColor: 'transparent',
                        color: colorConfig.secondary
                    }
                }
            });
        }
    }, [flash, colorConfig]); // Trigger when flash props change

    return null;
}
