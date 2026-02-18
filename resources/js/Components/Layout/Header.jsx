import { useState } from 'react';
import {
    AppShell,
    Group,
    Burger,
    ActionIcon,
    Menu,
    Avatar,
    Text,
    Tooltip,
    Box,
    Indicator,
    ColorSwatch,
    ColorPicker,
    Popover,
    SimpleGrid,
    Stack,
    Divider,
} from '@mantine/core';
import { useDisclosure, useMediaQuery } from '@mantine/hooks';
import { useLayout } from './AdminLayout';
import { useTheme } from '../../theme';
import { Logo } from '../Logo';

const UserIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
    </svg>
);

const LogoutIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>
    </svg>
);


// Color Picker Component with both presets and custom picker
function ThemeColorPicker() {
    const { primaryColor, setColor, presetColors, gradient } = useTheme();
    const [opened, { close, toggle }] = useDisclosure(false);
    const [customColor, setCustomColor] = useState(primaryColor);

    const handleCustomColorChange = (color) => {
        setCustomColor(color);
    };

    const applyCustomColor = () => {
        setColor(customColor);
        close();
    };

    return (
        <Popover
            opened={opened}
            onChange={toggle}
            position="bottom-end"
            shadow="md"
            withinPortal
            width={260}
        >
            <Popover.Target>
                <Tooltip label="Change theme color">
                    <ActionIcon
                        variant="subtle"
                        size="lg"
                        radius="md"
                        onClick={toggle}
                    >
                        <Box
                            style={{
                                width: 24,
                                height: 24,
                                borderRadius: '50%',
                                background: `linear-gradient(135deg, ${gradient.from}, ${gradient.to})`,
                            }}
                        />
                    </ActionIcon>
                </Tooltip>
            </Popover.Target>
            <Popover.Dropdown p="md">
                <Stack gap="sm">
                    <Text size="sm" fw={500}>Preset Colors</Text>
                    <SimpleGrid cols={4} spacing="xs">
                        {presetColors.map((color) => (
                            <Tooltip key={color.name} label={color.name} withArrow>
                                <ColorSwatch
                                    color={color.hex}
                                    size={32}
                                    radius="md"
                                    onClick={() => {
                                        setColor(color.hex);
                                        close();
                                    }}
                                    style={{
                                        cursor: 'pointer',
                                        border: primaryColor === color.hex
                                            ? '3px solid #1a1b1e'
                                            : '2px solid rgba(0,0,0,0.1)',
                                        transform: primaryColor === color.hex ? 'scale(1.1)' : 'scale(1)',
                                        transition: 'all 0.15s ease',
                                    }}
                                />
                            </Tooltip>
                        ))}
                    </SimpleGrid>

                    <Divider label="or pick custom" labelPosition="center" />

                    <ColorPicker
                        value={customColor}
                        onChange={handleCustomColorChange}
                        format="hex"
                        size="sm"
                        fullWidth
                    />

                    <ActionIcon
                        variant="filled"
                        size="md"
                        radius="md"
                        onClick={applyCustomColor}
                        style={{
                            background: `linear-gradient(135deg, ${customColor}, ${customColor})`,
                            width: '100%',
                        }}
                    >
                        <Text size="xs" c="white" fw={500}>Apply Color</Text>
                    </ActionIcon>
                </Stack>
            </Popover.Dropdown>
        </Popover>
    );
}

// Mobile Theme Selector in dropdown
function MobileThemeSelector() {
    const { primaryColor, setColor, presetColors, gradient } = useTheme();
    const [showPicker, setShowPicker] = useState(false);
    const [customColor, setCustomColor] = useState(primaryColor);

    return (
        <>
            <Menu.Label>Theme Color</Menu.Label>
            <Box px="xs" pb="xs">
                <SimpleGrid cols={4} spacing={6}>
                    {presetColors.map((color) => (
                        <Tooltip key={color.name} label={color.name} withArrow>
                            <ColorSwatch
                                color={color.hex}
                                size={28}
                                radius="md"
                                onClick={() => setColor(color.hex)}
                                style={{
                                    cursor: 'pointer',
                                    border: primaryColor === color.hex
                                        ? '3px solid #1a1b1e'
                                        : '2px solid rgba(0,0,0,0.1)',
                                    transform: primaryColor === color.hex ? 'scale(1.1)' : 'scale(1)',
                                    transition: 'all 0.15s ease',
                                }}
                            />
                        </Tooltip>
                    ))}
                </SimpleGrid>
                {showPicker && (
                    <Box mt="sm">
                        <ColorPicker
                            value={customColor}
                            onChange={setCustomColor}
                            format="hex"
                            size="xs"
                            fullWidth
                        />
                        <ActionIcon
                            variant="filled"
                            size="sm"
                            radius="md"
                            mt="xs"
                            onClick={() => {
                                setColor(customColor);
                                setShowPicker(false);
                            }}
                            style={{
                                background: customColor,
                                width: '100%',
                            }}
                        >
                            <Text size="xs" c="white">Apply</Text>
                        </ActionIcon>
                    </Box>
                )}
                <Text
                    size="xs"
                    c="dimmed"
                    mt="xs"
                    style={{ cursor: 'pointer', textDecoration: 'underline' }}
                    onClick={() => setShowPicker(!showPicker)}
                >
                    {showPicker ? 'Hide picker' : 'Custom color...'}
                </Text>
            </Box>
            <Menu.Divider />
        </>
    );
}

export default function Header() {
    const { mobileOpened, toggleMobile, user } = useLayout();
    const { ui, gradient, colorConfig } = useTheme();
    const isMobile = useMediaQuery('(max-width: 768px)');

    return (
        <AppShell.Header
            style={{
                background: ui.headerBg,
                backdropFilter: 'blur(10px)',
                borderBottom: `1px solid ${ui.border}`,
            }}
        >
            <Group h="100%" px="md" justify="space-between">
                {/* Left side - Logo and mobile burger */}
                <Group>
                    <Burger
                        opened={mobileOpened}
                        onClick={toggleMobile}
                        hiddenFrom="sm"
                        size="sm"
                    />
                    <Logo size="md" />
                </Group>

                {/* Right side - Actions */}
                <Group gap="sm">
                    {/* Color Picker - Desktop only */}
                    {!isMobile && <ThemeColorPicker />}

                    {/* User Menu */}
                    <Menu shadow="md" width={240} position="bottom-end">
                        <Menu.Target>
                            <ActionIcon variant="subtle" size="lg" radius="xl">
                                <Avatar
                                    size="sm"
                                    radius="md"
                                    styles={{
                                        placeholder: {
                                            background: `linear-gradient(135deg, ${gradient.from} 0%, ${gradient.to} 100%)`,
                                            color: 'white',
                                        }
                                    }}
                                    style={{ cursor: 'pointer' }}
                                >
                                    {user?.name?.charAt(0) || 'U'}
                                </Avatar>
                            </ActionIcon>
                        </Menu.Target>

                        <Menu.Dropdown>
                            <Menu.Label>
                                <Text size="sm" fw={500}>{user?.name || 'Guest User'}</Text>
                                <Text size="xs" c="dimmed">{user?.email || 'guest@example.com'}</Text>
                            </Menu.Label>
                            <Menu.Divider />

                            {/* Theme selection in mobile dropdown */}
                            {isMobile && <MobileThemeSelector />}

                            <Menu.Divider />
                            <Menu.Item color="red" leftSection={<LogoutIcon />}>
                                Logout
                            </Menu.Item>
                        </Menu.Dropdown>
                    </Menu>
                </Group>
            </Group>
        </AppShell.Header>
    );
}
