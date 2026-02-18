import { TextInput } from '@mantine/core';
import { useTheme } from '../../theme';

/**
 * Themed TextInput Component
 *
 * A TextInput that automatically uses the current theme colors
 * for focus states and styling.
 *
 * @param {Object} props - All Mantine TextInput props are supported
 */
export default function ThemedInput({ styles, ...props }) {
    const { colorConfig } = useTheme();

    return (
        <TextInput
            styles={styles}
            vars={(theme) => ({
                root: {
                    '--input-bd-focus': colorConfig.primary,
                },
            })}
            {...props}
        />
    );
}
