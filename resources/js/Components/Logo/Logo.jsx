import { Text, Group, Box } from '@mantine/core';
import { Link } from '@inertiajs/react';
import { useTheme } from '../../theme';

/**
 * Logo Component
 *
 * @param {Object} props
 * @param {boolean} props.collapsed - Whether sidebar is collapsed (show only icon)
 * @param {string} props.size - Size variant: 'sm', 'md', 'lg'
 */
export default function Logo({ collapsed = false, size = 'md' }) {
    const { gradient, colorConfig } = useTheme();

    const sizes = {
        sm: { icon: 24, text: 'md', fontSize: 12 },
        md: { icon: 32, text: 'lg', fontSize: 16 },
        lg: { icon: 40, text: 'xl', fontSize: 20 },
    };

    const currentSize = sizes[size] || sizes.md;

    // Logo Icon - just the F letter with gradient
    const LogoIcon = () => (
        <Box
            style={{
                width: currentSize.icon,
                height: currentSize.icon,
                borderRadius: 8,
                background: `linear-gradient(135deg, ${gradient.from} 0%, ${gradient.to} 100%)`,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                color: 'white',
                fontWeight: 900,
                fontSize: currentSize.fontSize,
            }}
        >
            E
        </Box>
    );

    if (collapsed) {
        return (
            <Link href="/admin/dashboard" style={{ textDecoration: 'none' }}>
                <LogoIcon />
            </Link>
        );
    }

    return (
        <Link href="/admin/dashboard" style={{ textDecoration: 'none' }}>
            <Group gap="xs">
                <LogoIcon />
                {/* Text with gradient color applied to the text itself */}
                <Text
                    fw={900}
                    size={currentSize.text}
                    style={{
                        color: colorConfig.primary,
                    }}
                >
                    ePay
                </Text>
            </Group>
        </Link>
    );
}
