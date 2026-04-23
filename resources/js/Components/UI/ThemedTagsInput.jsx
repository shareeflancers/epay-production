import { TagsInput } from '@mantine/core';
import { useTheme } from '../../theme';

/**
 * Themed TagsInput Component
 *
 * A TagsInput that automatically uses the current theme colors.
 *
 * @param {Object} props - All Mantine TagsInput props are supported
 */
export default function ThemedTagsInput({ styles, ...props }) {
    const { colorConfig } = useTheme();

    const themedStyles = {
        pill: {
            backgroundColor: colorConfig.light,
            color: colorConfig.primary,
            border: `1px solid ${colorConfig.primary}33`,
        },
        input: {
            '&:focus-within': {
                borderColor: colorConfig.primary,
            },
        },
        ...styles,
    };

    return (
        <TagsInput
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
