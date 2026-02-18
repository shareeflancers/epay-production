import { Modal } from '@mantine/core';
import { useTheme } from '../../theme';

/**
 * Themed Modal Component
 *
 * A Modal with themed header - primary background and white title.
 *
 * @param {Object} props - All Mantine Modal props are supported
 * @param {string} props.title - Modal title (will be white on themed background)
 */
export default function ThemedModal({ title, children, styles, ...props }) {
    const { colorConfig } = useTheme();

    const themedStyles = {
        header: {
            backgroundColor: colorConfig.primary,
            padding: '16px 20px',
        },
        title: {
            color: 'white',
            fontWeight: 600,
            fontSize: '1.1rem',
        },
        close: {
            color: 'white',
            '&:hover': {
                backgroundColor: 'rgba(255, 255, 255, 0.1)',
            },
        },
        body: {
            padding: '20px',
        },
        ...styles,
    };

    return (
        <Modal
            title={title}
            styles={themedStyles}
            {...props}
        >
            {children}
        </Modal>
    );
}
