import { Select } from '@mantine/core';
import { useTheme } from '../../theme';

/**
 * Themed Select Component
 *
 * A Select/Dropdown that automatically uses the current theme colors.
 *
 * @param {Object} props - All Mantine Select props are supported
 */
export default function ThemedSelect({ styles, ...props }) {
    const { colorConfig } = useTheme();

    const themedStyles = {
        option: {
            '&[data-selected]': {
                backgroundColor: colorConfig.primary,
            },
            '&[data-hovered]': {
                backgroundColor: colorConfig.light,
            },
        },
        ...styles,
    };

    return (
        <Select
            styles={themedStyles}
            vars={(theme) => ({
                root: {
                    '--input-bd-focus': colorConfig.primary,
                },
            })}
            {...props}
        />
    );
}
