import { Switch } from '@mantine/core';
import { useTheme } from '../../theme';

/**
 * Themed Switch Component
 *
 * A Switch/Toggle that automatically uses the current theme colors.
 * Applies theme to: track, thumb (dot), border, and hover states.
 *
 * @param {Object} props - All Mantine Switch props are supported
 */
export default function ThemedSwitch({ checked, styles, ...props }) {
    const { colorConfig } = useTheme();

    const themedStyles = {
        track: {
            backgroundColor: checked ? colorConfig.primary : undefined,
            borderColor: checked ? colorConfig.primary : colorConfig.primary,
            cursor: 'pointer',
            '&:hover': {
                backgroundColor: checked ? colorConfig.hover : colorConfig.light,
            },
        },
        thumb: {
            backgroundColor: checked ? 'white' : colorConfig.primary,
            borderColor: checked ? 'white' : colorConfig.primary,
        },
        label: {
            cursor: 'pointer',
        },
        ...styles,
    };

    return (
        <Switch
            checked={checked}
            styles={themedStyles}
            {...props}
        />
    );
}
