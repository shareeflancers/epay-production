import { AppShell, Text, Group } from '@mantine/core';
import { useTheme } from '../../theme';

export default function Footer() {
    const { ui } = useTheme();

    return (
        <AppShell.Footer
            p="sm"
            style={{
                background: ui.headerBg,
                backdropFilter: 'blur(10px)',
                borderTop: `1px solid ${ui.border}`,
            }}
        >
            <Group justify="space-between" px="md">
                <Text size="xs" c="dimmed">
                    © 2026 FeeMS. All rights reserved.
                </Text>
                <Group gap="md">
                    <Text size="xs" c="dimmed" style={{ cursor: 'pointer' }}>
                        Privacy Policy
                    </Text>
                    <Text size="xs" c="dimmed" style={{ cursor: 'pointer' }}>
                        Terms of Service
                    </Text>
                </Group>
            </Group>
        </AppShell.Footer>
    );
}
