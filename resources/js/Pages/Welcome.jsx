import { useState } from 'react';
import {
    Container,
    Title,
    Text,
    Button,
    Group,
    Stack,
    Card,
    Badge,
    SimpleGrid,
    ThemeIcon,
    ActionIcon,
    Tooltip,
    Box,
    ColorSwatch,
    Popover,
    ColorPicker,
    Divider,
} from '@mantine/core';
import { useDisclosure } from '@mantine/hooks';
import { useTheme } from '../theme';
import axios from 'axios';

// Icons as simple SVG components
const RocketIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/>
        <path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/>
        <path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/>
        <path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/>
    </svg>
);

const CodeIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <polyline points="16 18 22 12 16 6"/>
        <polyline points="8 6 2 12 8 18"/>
    </svg>
);

const PaletteIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <circle cx="13.5" cy="6.5" r=".5"/>
        <circle cx="17.5" cy="10.5" r=".5"/>
        <circle cx="8.5" cy="7.5" r=".5"/>
        <circle cx="6.5" cy="12.5" r=".5"/>
        <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.555C21.965 6.012 17.461 2 12 2z"/>
    </svg>
);

const BoltIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
    </svg>
);

const features = [
    {
        icon: CodeIcon,
        title: 'Modern Stack',
        description: 'Laravel 12, React 19, and cutting-edge tools',
    },
    {
        icon: PaletteIcon,
        title: 'Beautiful UI',
        description: 'Mantine components with custom theming',
    },
    {
        icon: BoltIcon,
        title: 'Lightning Fast',
        description: 'Vite dev server with instant HMR',
    },
    {
        icon: RocketIcon,
        title: 'Production Ready',
        description: 'Optimized builds and best practices',
    },
];

export default function Welcome() {
    const { primaryColor, setColor, presetColors, colorConfig, ui, gradient } = useTheme();
    const [opened, { close, toggle }] = useDisclosure(false);
    const [customColor, setCustomColor] = useState(primaryColor);

    const applyCustomColor = () => {
        setColor(customColor);
        close();
    };

    const [consumerNumber, setConsumerNumber] = useState('');
    const [searching, setSearching] = useState(false);
    const [searchResult, setSearchResult] = useState(null);
    const [error, setError] = useState('');

    const handleSearch = async () => {
        if (!consumerNumber) {
            setError('Please enter a consumer number');
            return;
        }

        setSearching(true);
        setError('');
        setSearchResult(null);

        try {
            const response = await axios.get('/challan/search', {
                params: { consumer_number: consumerNumber }
            });
            if (response.data.success) {
                setSearchResult(response.data.data);
            }
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to find challan. Please check the number.');
        } finally {
            setSearching(false);
        }
    };

    return (
        <Box
            style={{
                minHeight: '100vh',
                background: `linear-gradient(135deg, ${gradient.from} 0%, ${gradient.to} 100%)`,
                position: 'relative',
                overflow: 'hidden',
            }}
        >
            {/* Animated background elements */}
            <Box
                style={{
                    position: 'absolute',
                    top: '-50%',
                    left: '-50%',
                    width: '200%',
                    height: '200%',
                    background: 'radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px)',
                    backgroundSize: '50px 50px',
                    animation: 'float 20s ease-in-out infinite',
                }}
            />

            {/* Theme Color Picker */}
            <Box
                style={{
                    position: 'fixed',
                    top: 20,
                    right: 20,
                    zIndex: 1000,
                }}
            >
                <Popover
                    opened={opened}
                    onChange={toggle}
                    position="bottom-end"
                    shadow="md"
                    withinPortal
                    width={260}
                >
                    <Popover.Target>
                        <ActionIcon
                            size="xl"
                            radius="xl"
                            onClick={toggle}
                            style={{
                                background: ui.cardBg,
                                backdropFilter: 'blur(10px)',
                                boxShadow: '0 4px 20px rgba(0,0,0,0.15)',
                            }}
                        >
                            <Box
                                style={{
                                    width: 28,
                                    height: 28,
                                    borderRadius: '50%',
                                    background: `linear-gradient(135deg, ${gradient.from}, ${gradient.to})`,
                                    border: '2px solid rgba(255,255,255,0.3)',
                                }}
                            />
                        </ActionIcon>
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
                                onChange={setCustomColor}
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
                                    background: customColor,
                                    width: '100%',
                                }}
                            >
                                <Text size="xs" c="white" fw={500}>Apply Color</Text>
                            </ActionIcon>
                        </Stack>
                    </Popover.Dropdown>
                </Popover>
            </Box>

            <Container size="lg" py={100} style={{ position: 'relative', zIndex: 1 }}>
                <Stack align="center" gap="xl">
                    {/* Hero Section */}
                    <Card
                        shadow="xl"
                        padding="xl"
                        radius="lg"
                        style={{
                            background: ui.cardBg,
                            backdropFilter: 'blur(20px)',
                            border: '1px solid rgba(255,255,255,0.2)',
                            textAlign: 'center',
                            maxWidth: 800,
                            width: '100%',
                        }}
                    >
                        <Stack gap="lg">
                            <Badge
                                size="lg"
                                variant="gradient"
                                gradient={{ from: gradient.from, to: gradient.to }}
                                style={{ alignSelf: 'center' }}
                            >
                                🚀 Ready for Production
                            </Badge>

                            <Title
                                order={1}
                                style={{
                                    fontSize: 'clamp(2rem, 5vw, 3.5rem)',
                                    fontWeight: 900,
                                    background: `linear-gradient(135deg, ${gradient.from} 0%, ${gradient.to} 50%, ${colorConfig.light} 100%)`,
                                    WebkitBackgroundClip: 'text',
                                    WebkitTextFillColor: 'transparent',
                                    backgroundClip: 'text',
                                }}
                            >
                                Laravel + React
                            </Title>

                            <Text size="xl" c="dimmed" maw={600} mx="auto">
                                A modern full-stack starter with{' '}
                                <Text component="span" fw={700} c="dark">
                                    Inertia.js
                                </Text>
                                ,{' '}
                                <Text component="span" fw={700} c="dark">
                                    Mantine UI
                                </Text>
                                , and{' '}
                                <Text component="span" fw={700} c="dark">
                                    Tailwind CSS
                                </Text>
                            </Text>

                            <Group justify="center" gap="md">
                                <Button
                                    size="lg"
                                    variant="gradient"
                                    gradient={{ from: gradient.from, to: gradient.to }}
                                    radius="xl"
                                    leftSection={<RocketIcon />}
                                >
                                    Get Started
                                </Button>
                                <Button
                                    size="lg"
                                    variant="outline"
                                    radius="xl"
                                    color="dark"
                                >
                                    Documentation
                                </Button>
                            </Group>
                        </Stack>
                    </Card>

                    {/* Features Grid - all using theme color */}
                    <SimpleGrid cols={{ base: 1, sm: 2 }} spacing="lg" style={{ maxWidth: 900, width: '100%' }}>
                        {features.map((feature) => (
                            <Card
                                key={feature.title}
                                shadow="md"
                                padding="lg"
                                radius="md"
                                style={{
                                    background: ui.cardBg,
                                    backdropFilter: 'blur(10px)',
                                    border: '1px solid rgba(255,255,255,0.1)',
                                    transition: 'transform 0.2s ease, box-shadow 0.2s ease',
                                    cursor: 'pointer',
                                }}
                                onMouseEnter={(e) => {
                                    e.currentTarget.style.transform = 'translateY(-4px)';
                                    e.currentTarget.style.boxShadow = '0 12px 40px rgba(0,0,0,0.2)';
                                }}
                                onMouseLeave={(e) => {
                                    e.currentTarget.style.transform = 'translateY(0)';
                                    e.currentTarget.style.boxShadow = '';
                                }}
                            >
                                <Group>
                                    <ThemeIcon
                                        size="xl"
                                        radius="md"
                                        variant="gradient"
                                        gradient={{ from: gradient.from, to: gradient.to }}
                                    >
                                        <feature.icon />
                                    </ThemeIcon>
                                    <div>
                                        <Text fw={700} size="lg">
                                            {feature.title}
                                        </Text>
                                        <Text size="sm" c="dimmed">
                                            {feature.description}
                                        </Text>
                                    </div>
                                </Group>
                            </Card>
                        ))}
                    </SimpleGrid>

                    {/* Find Your Challan Section */}
                    <Card
                        id="find-challan"
                        shadow="xl"
                        padding="xl"
                        radius="lg"
                        style={{
                            background: ui.cardBg,
                            backdropFilter: 'blur(20px)',
                            border: `2px solid ${gradient.from}44`,
                            textAlign: 'center',
                            maxWidth: 800,
                            width: '100%',
                            marginTop: 40,
                            position: 'relative',
                            overflow: 'hidden',
                        }}
                    >
                        <Box
                           style={{
                               position: 'absolute',
                               top: 0,
                               left: 0,
                               width: '100%',
                               height: 4,
                               background: `linear-gradient(90deg, ${gradient.from}, ${gradient.to})`
                           }}
                        />

                            <Stack gap="lg">
                                <Title order={2} style={{ fontWeight: 800 }}>
                                    📑 Find Your Challan
                                </Title>
                                <Text c="dimmed">
                                    Enter your consumer number below to find and generate your fee challan form.
                                </Text>

                                <Group grow align="flex-end">
                                    <Box style={{ textAlign: 'left' }}>
                                        <Text size="sm" fw={500} mb={5}>Consumer Number</Text>
                                        <input
                                            type="text"
                                            placeholder="e.g. 12345678"
                                            value={consumerNumber}
                                            onChange={(e) => setConsumerNumber(e.target.value)}
                                            style={{
                                                width: '100%',
                                                padding: '12px 16px',
                                                borderRadius: '12px',
                                                border: '2px solid rgba(0,0,0,0.05)',
                                                background: 'rgba(255,255,255,0.8)',
                                                outline: 'none',
                                                fontSize: '16px',
                                                transition: 'all 0.2s ease',
                                                boxShadow: 'inset 0 2px 4px rgba(0,0,0,0.02)'
                                            }}
                                            onFocus={(e) => {
                                                e.target.style.borderColor = gradient.from;
                                                e.target.style.boxShadow = `0 0 0 4px ${gradient.from}22`;
                                            }}
                                            onBlur={(e) => {
                                                e.target.style.borderColor = 'rgba(0,0,0,0.05)';
                                                e.target.style.boxShadow = 'inset 0 2px 4px rgba(0,0,0,0.02)';
                                            }}
                                        />
                                    </Box>
                                    <Button
                                        size="lg"
                                        variant="gradient"
                                        gradient={{ from: gradient.from, to: gradient.to }}
                                        radius="md"
                                        onClick={handleSearch}
                                        loading={searching}
                                        style={{ height: 48 }}
                                    >
                                        Search Challan
                                    </Button>
                                </Group>

                                {error && (
                                    <Text c="red" size="sm" fw={600}>
                                        ⚠️ {error}
                                    </Text>
                                )}

                                {searchResult && (
                                    <Card padding="xl" radius="md" withBorder style={{ backgroundColor: 'rgba(255,255,255,0.5)', borderStyle: 'dashed' }}>
                                        <Stack gap="md" align="center">
                                            <Badge size="lg" variant="light" color="green">Challan Found!</Badge>
                                            <SimpleGrid cols={{ base: 1, sm: 3 }} spacing="xl" style={{ width: '100%' }}>
                                                <Stack gap={0}>
                                                    <Text size="xs" c="dimmed" tt="uppercase" ls={1} fw={700}>Student Name</Text>
                                                    <Text fw={600} size="lg">{searchResult.name}</Text>
                                                </Stack>
                                                <Stack gap={0}>
                                                    <Text size="xs" c="dimmed" tt="uppercase" ls={1} fw={700}>Challan No</Text>
                                                    <Text fw={600} size="lg">{searchResult.challan_no}</Text>
                                                </Stack>
                                                <Stack gap={0}>
                                                    <Text size="xs" c="dimmed" tt="uppercase" ls={1} fw={700}>Amount</Text>
                                                    <Text fw={800} size="xl" color={gradient.from}>Rs. {searchResult.amount}</Text>
                                                </Stack>
                                            </SimpleGrid>
                                            <Button
                                                component="a"
                                                href={searchResult.view_url}
                                                target="_blank"
                                                variant="filled"
                                                color="dark"
                                                radius="xl"
                                                size="md"
                                                mt="md"
                                                leftSection={<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>}
                                            >
                                                Generate Printable Form
                                            </Button>
                                        </Stack>
                                    </Card>
                                )}

                                {/* Theme Demo Section */}
                                <Card
                                    shadow="md"
                                    padding="lg"
                                    radius="md"
                                    style={{
                                        background: ui.cardBg,
                                        backdropFilter: 'blur(10px)',
                                        border: '1px solid rgba(255,255,255,0.1)',
                                        textAlign: 'center',
                                        maxWidth: 600,
                                        width: '100%',
                                    }}
                                >
                                    <Stack gap="sm">
                                        <Text fw={700} size="lg">
                                            🎨 Theme System Ready
                                        </Text>
                                        <Text size="sm" c="dimmed">
                                            Current color:{' '}
                                            <Badge
                                                variant="light"
                                                style={{
                                                    backgroundColor: colorConfig.light,
                                                    color: colorConfig.primary
                                                }}
                                            >
                                                {primaryColor}
                                            </Badge>
                                        </Text>
                                        <Text size="xs" c="dimmed">
                                            Click the color button in the top-right corner to change colors.
                                            Pick from presets or use the color picker for any custom color!
                                        </Text>
                                    </Stack>
                                </Card>

                                {/* Footer */}
                                <Text size="sm" c="dimmed" ta="center" mt="xl">
                                    Built with ❤️ using Laravel 12, React 19, Mantine 8, and Tailwind CSS 4
                                </Text>
                            </Stack>
                        </Card>
                    </Stack>
            </Container>

            <style>{`
                @keyframes float {
                    0%, 100% { transform: translate(0, 0) rotate(0deg); }
                    25% { transform: translate(10px, 10px) rotate(1deg); }
                    50% { transform: translate(0, 20px) rotate(0deg); }
                    75% { transform: translate(-10px, 10px) rotate(-1deg); }
                }
            `}</style>
        </Box>
    );
}
