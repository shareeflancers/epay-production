import { Button } from '@mantine/core';
import { useTheme } from '../../theme';

/**
 * Themed Button Component
 *
 * A Button that automatically uses the current theme colors.
 *
 * Variants:
 * - 'primary' (default): Gradient button with theme colors
 * - 'secondary': Light background with theme text
 * - 'subtle': Text-only button with theme color
 * - 'outline': Outlined with theme color
 * - 'danger': Red delete/danger button (unchanged)
 *
 * @param {Object} props - All Mantine Button props are supported
 * @param {string} props.themeVariant - 'primary' | 'secondary' | 'subtle' | 'outline' | 'danger'
 */
export default function ThemedButton({
    themeVariant = 'primary',
    style,
    children,
    ...props
}) {
    const { gradient, colorConfig } = useTheme();

    // Get themed button props based on variant
    const getThemedProps = () => {
        switch (themeVariant) {
            case 'primary':
                return {
                    variant: 'gradient',
                    gradient: { from: gradient.from, to: gradient.to },
                };
            case 'secondary':
                return {
                    variant: 'light',
                    style: {
                        backgroundColor: colorConfig.light,
                        color: colorConfig.primary,
                        ...style,
                    },
                };
            case 'subtle':
                return {
                    variant: 'subtle',
                    style: {
                        color: colorConfig.primary,
                        ...style,
                    },
                };
            case 'outline':
                return {
                    variant: 'outline',
                    style: {
                        borderColor: colorConfig.primary,
                        color: colorConfig.primary,
                        ...style,
                    },
                };
            case 'danger':
                return {
                    color: 'red',
                    style,
                };
            default:
                return {
                    variant: 'gradient',
                    gradient: { from: gradient.from, to: gradient.to },
                };
        }
    };

    const themedProps = getThemedProps();

    return (
        <Button
            {...themedProps}
            {...props}
        >
            {children}
        </Button>
    );
}
