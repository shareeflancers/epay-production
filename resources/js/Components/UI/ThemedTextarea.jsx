import { Textarea } from '@mantine/core';
import { useTheme } from '../../theme';

/**
 * Themed Textarea Component
 *
 * A Textarea that automatically uses the current theme colors
 * for focus states and styling.
 *
 * @param {Object} props - All Mantine Textarea props are supported
 */
export default function ThemedTextarea({ styles, ...props }) {
    const { colorConfig } = useTheme();


    return (
        <Textarea
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
