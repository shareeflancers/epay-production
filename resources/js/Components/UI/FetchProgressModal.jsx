import { useState, useEffect, useRef, useCallback } from 'react';
import { Modal, RingProgress, Text, Stack, Box, Center, ThemeIcon, Tooltip } from '@mantine/core';
import { useTheme } from '../../theme';

/**
 * FetchProgressModal Component
 *
 * Shows a themed loading modal with animated progress ring
 * when fetching data from an external API.
 *
 * @param {boolean} opened - Whether the modal is open
 * @param {Function} onClose - Callback when modal closes
 * @param {string} fetchUrl - The API URL to fetch from
 * @param {string} fetchLabel - Display label (e.g., "Students")
 */

// Status phases
const STATUS = {
    IDLE: 'idle',
    CONNECTING: 'connecting',
    FETCHING: 'fetching',
    COMPLETE: 'complete',
    ERROR: 'error',
};

const statusMessages = {
    [STATUS.IDLE]: 'Preparing...',
    [STATUS.CONNECTING]: 'Connecting to server...',
    [STATUS.FETCHING]: 'Fetching data...',
    [STATUS.COMPLETE]: 'Complete!',
    [STATUS.ERROR]: 'Failed!',
};

// Icons for states
const CheckIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
        <polyline points="20 6 9 17 4 12" />
    </svg>
);

const ErrorIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
        <circle cx="12" cy="12" r="10" /><line x1="15" y1="9" x2="9" y2="15" /><line x1="9" y1="9" x2="15" y2="15" />
    </svg>
);

const DownloadIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
        <polyline points="7 10 12 15 17 10" />
        <line x1="12" y1="15" x2="12" y2="3" />
    </svg>
);

const CopyIcon = ({ size = 14 }) => (
    <svg xmlns="http://www.w3.org/2000/svg" width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <rect x="9" y="9" width="13" height="13" rx="2" ry="2" /><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
    </svg>
);

/**
 * Formats the error response into a single readable string.
 * Handles: { message, errors: { field: [msgs] } } for validation,
 *          { message } for general errors, or plain string.
 */
function formatErrorText(message, errors) {
    const parts = [];
    if (message) parts.push(message);

    if (errors && typeof errors === 'object') {
        Object.entries(errors).forEach(([field, msgs]) => {
            const fieldErrors = Array.isArray(msgs) ? msgs.join(', ') : msgs;
            parts.push(`${field}: ${fieldErrors}`);
        });
    }

    return parts.join('\n');
}

export default function FetchProgressModal({ opened, onClose, fetchUrl, fetchLabel }) {
    const { colorConfig, ui } = useTheme();
    const [progress, setProgress] = useState(0);
    const [status, setStatus] = useState(STATUS.IDLE);
    const [errorMessage, setErrorMessage] = useState('');
    const [errorDetails, setErrorDetails] = useState('');
    const [copied, setCopied] = useState(false);
    const [recordCount, setRecordCount] = useState(null);
    const [syncStats, setSyncStats] = useState(null);
    const intervalRef = useRef(null);
    const abortRef = useRef(null);

    // Simulated progress animation
    const startProgressSimulation = useCallback(() => {
        let current = 0;
        setProgress(0);
        setStatus(STATUS.CONNECTING);

        intervalRef.current = setInterval(() => {
            current += Math.random() * 8 + 4;

            if (current >= 15 && current < 20) {
                setStatus(STATUS.FETCHING);
            }

            if (current >= 90) {
                current = 90;
                clearInterval(intervalRef.current);
            }

            setProgress(Math.round(current));
        }, 400);
    }, []);

    // Cleanup intervals
    const cleanup = useCallback(() => {
        if (intervalRef.current) {
            clearInterval(intervalRef.current);
            intervalRef.current = null;
        }
        if (abortRef.current) {
            abortRef.current.abort();
            abortRef.current = null;
        }
    }, []);

    // Copy error to clipboard
    const handleCopyError = useCallback(() => {
        if (!errorDetails) return;
        navigator.clipboard.writeText(errorDetails).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        });
    }, [errorDetails]);

    // Start fetch when modal opens
    useEffect(() => {
        if (!opened || !fetchUrl) return;

        // Reset state
        setProgress(0);
        setStatus(STATUS.IDLE);
        setErrorMessage('');
        setErrorDetails('');
        setCopied(false);
        setRecordCount(null);
        setSyncStats(null);

        const abortController = new AbortController();
        abortRef.current = abortController;

        startProgressSimulation();

        fetch(fetchUrl, {
            signal: abortController.signal,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(response => response.json())
            .then(data => {
                cleanup();

                if (data.success) {
                    setProgress(100);
                    setStatus(STATUS.COMPLETE);

                    if (data.stats) {
                        setSyncStats(data.stats);
                    } else if (data.data && Array.isArray(data.data)) {
                        setRecordCount(data.data.length);
                    }
                } else {
                    setProgress(100);
                    setStatus(STATUS.ERROR);
                    const shortMsg = data.message || 'An unknown error occurred';
                    setErrorMessage(shortMsg);
                    setErrorDetails(formatErrorText(data.message, data.errors));
                }
            })
            .catch(err => {
                cleanup();
                if (err.name === 'AbortError') return;
                setProgress(100);
                setStatus(STATUS.ERROR);
                const msg = err.message || 'Network error';
                setErrorMessage(msg);
                setErrorDetails(msg);
            });

        return cleanup;
    }, [opened, fetchUrl]);

    // Auto-close after success
    useEffect(() => {
        if (status === STATUS.COMPLETE) {
            const timer = setTimeout(() => {
                onClose();
            }, 2000);
            return () => clearTimeout(timer);
        }
    }, [status, onClose]);

    const handleClose = () => {
        cleanup();
        onClose();
    };

    // Ring color based on status
    const getRingColor = () => {
        if (status === STATUS.ERROR) return '#e03131';
        if (status === STATUS.COMPLETE) return '#2f9e44';
        return colorConfig.primary;
    };

    // Center content of ring
    const renderRingLabel = () => {
        if (status === STATUS.COMPLETE) {
            return (
                <ThemeIcon color="green" variant="light" radius="xl" size={56}>
                    <CheckIcon />
                </ThemeIcon>
            );
        }
        if (status === STATUS.ERROR) {
            return (
                <ThemeIcon color="red" variant="light" radius="xl" size={56}>
                    <ErrorIcon />
                </ThemeIcon>
            );
        }
        return (
            <Text fw={700} size="xl" style={{ color: colorConfig.primary }}>
                {progress}%
            </Text>
        );
    };

    // Error display component — compact, hoverable, copyable
    const renderErrorBox = () => {
        if (status !== STATUS.ERROR) return null;

        return (
            <Tooltip
                label={
                    <Box style={{ maxWidth: 320, whiteSpace: 'pre-wrap', wordBreak: 'break-word', fontSize: 12 }}>
                        {errorDetails}
                    </Box>
                }
                multiline
                position="bottom"
                withArrow
                arrowSize={8}
                events={{ hover: true, focus: true, touch: true }}
                styles={{
                    tooltip: {
                        backgroundColor: '#1a1b1e',
                        color: '#fff',
                        border: '1px solid #373A40',
                        padding: '10px 14px',
                        maxWidth: 350,
                        boxShadow: '0 8px 24px rgba(0,0,0,0.25)',
                    },
                }}
            >
                <Box
                    onClick={handleCopyError}
                    style={{
                        width: '100%',
                        padding: '8px 12px',
                        borderRadius: 8,
                        backgroundColor: 'rgba(224, 49, 49, 0.08)',
                        border: '1px solid rgba(224, 49, 49, 0.2)',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        gap: 8,
                        transition: 'all 0.2s ease',
                        maxWidth: '100%',
                    }}
                    onMouseEnter={(e) => {
                        e.currentTarget.style.backgroundColor = 'rgba(224, 49, 49, 0.14)';
                        e.currentTarget.style.borderColor = 'rgba(224, 49, 49, 0.35)';
                    }}
                    onMouseLeave={(e) => {
                        e.currentTarget.style.backgroundColor = 'rgba(224, 49, 49, 0.08)';
                        e.currentTarget.style.borderColor = 'rgba(224, 49, 49, 0.2)';
                    }}
                >
                    <Text
                        size="xs"
                        fw={500}
                        style={{
                            color: '#e03131',
                            flex: 1,
                            overflow: 'hidden',
                            textOverflow: 'ellipsis',
                            whiteSpace: 'nowrap',
                        }}
                    >
                        {errorMessage}
                    </Text>
                    <Box
                        style={{
                            flexShrink: 0,
                            color: copied ? '#2f9e44' : '#868e96',
                            display: 'flex',
                            alignItems: 'center',
                            transition: 'color 0.2s ease',
                        }}
                        title={copied ? 'Copied!' : 'Click to copy'}
                    >
                        {copied ? (
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        ) : (
                            <CopyIcon />
                        )}
                    </Box>
                </Box>
            </Tooltip>
        );
    };

    return (
        <Modal
            opened={opened}
            onClose={handleClose}
            title={null}
            centered
            size="sm"
            radius="lg"
            withCloseButton={status === STATUS.ERROR || status === STATUS.COMPLETE}
            closeOnClickOutside={status === STATUS.ERROR || status === STATUS.COMPLETE}
            closeOnEscape={status === STATUS.ERROR || status === STATUS.COMPLETE}
            overlayProps={{ backgroundOpacity: 0.4, blur: 4 }}
            styles={{
                header: {
                    backgroundColor: 'transparent',
                    position: 'absolute',
                    right: 0,
                    top: 0,
                    zIndex: 10,
                },
                body: {
                    padding: '40px 30px 30px',
                },
                content: {
                    borderRadius: '16px',
                    overflow: 'hidden',
                    boxShadow: '0 20px 60px rgba(0, 0, 0, 0.15)',
                },
            }}
        >
            <Stack align="center" gap="lg">
                {/* Header icon */}
                <Box
                    style={{
                        width: 50,
                        height: 50,
                        borderRadius: '12px',
                        background: `linear-gradient(135deg, ${colorConfig.primary}, ${colorConfig.gradientTo})`,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        color: 'white',
                        boxShadow: `0 4px 15px ${colorConfig.primary}40`,
                    }}
                >
                    <DownloadIcon />
                </Box>

                {/* Title */}
                <Text fw={600} size="lg" ta="center" style={{ color: ui.text }}>
                    Fetching {fetchLabel}
                </Text>

                {/* Progress Ring */}
                <Center>
                    <RingProgress
                        size={160}
                        thickness={10}
                        roundCaps
                        sections={[{ value: progress, color: getRingColor() }]}
                        label={
                            <Center>{renderRingLabel()}</Center>
                        }
                        styles={{
                            root: {
                                filter: status === STATUS.COMPLETE
                                    ? 'drop-shadow(0 0 8px rgba(47, 158, 68, 0.3))'
                                    : status === STATUS.ERROR
                                        ? 'drop-shadow(0 0 8px rgba(224, 49, 49, 0.3))'
                                        : 'none',
                                transition: 'filter 0.3s ease',
                            },
                        }}
                    />
                </Center>

                {/* Status message */}
                <Box ta="center" style={{ width: '100%' }}>
                    {status !== STATUS.ERROR && (
                        <Text
                            size="sm"
                            fw={500}
                            style={{
                                color: status === STATUS.COMPLETE ? '#2f9e44' : ui.textMuted,
                                transition: 'color 0.3s ease',
                            }}
                        >
                            {statusMessages[status]}
                        </Text>
                    )}

                    {/* Error box — compact with hover & copy */}
                    {renderErrorBox()}

                    {status === STATUS.COMPLETE && (
                        syncStats ? (
                            <Box>
                                <Text size="sm" c="dimmed" mt={4}>
                                    {(syncStats.inserted === 0 && syncStats.updated === 0)
                                        ? "All data already up to date."
                                        : `Sync Complete: ${syncStats.inserted} New, ${syncStats.updated} Updated`
                                    }
                                </Text>
                                {syncStats.unchanged > 0 && (
                                    <Text size="xs" c="dimmed">
                                        ({syncStats.unchanged} records unchanged)
                                    </Text>
                                )}
                            </Box>
                        ) : (
                            recordCount !== null && (
                                <Text size="xs" c="dimmed" mt={4}>
                                    {recordCount.toLocaleString()} records fetched
                                </Text>
                            )
                        )
                    )}

                    {status !== STATUS.COMPLETE && status !== STATUS.ERROR && (
                        <Text size="xs" c="dimmed" mt={4}>
                            Please wait, this may take a moment...
                        </Text>
                    )}
                </Box>
            </Stack>
        </Modal>
    );
}
