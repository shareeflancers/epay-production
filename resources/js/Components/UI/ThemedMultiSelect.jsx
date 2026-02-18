import { MultiSelect } from '@mantine/core';
import { useTheme } from '../../theme';

/**
 * Themed MultiSelect Component
 *
 * A MultiSelect/Dropdown that automatically uses the current theme colors.
 *
 * @param {Object} props - All Mantine MultiSelect props are supported
 */
export default function ThemedMultiSelect({ styles, ...props }) {
    const { colorConfig } = useTheme();

    const themedStyles = {
        option: {
            '&[data-selected]': {
                backgroundColor: colorConfig.primary,
                color: 'white',
            },
            '&[data-hovered]': {
                backgroundColor: colorConfig.light,
                color: colorConfig.primary,
            },
        },
        ...styles,
    };

    return (
        <MultiSelect
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
