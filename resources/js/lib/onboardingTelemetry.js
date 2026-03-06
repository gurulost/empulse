import axios from 'axios';

const SESSION_PREFIX = 'empulse:onboarding-session:';
const ONCE_PREFIX = 'empulse:onboarding-once:';

const storage = () => {
    try {
        return window.sessionStorage;
    } catch {
        return null;
    }
};

const randomId = () => {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    return `sess-${Date.now()}-${Math.random().toString(16).slice(2)}`;
};

const sessionKey = (companyId) => `${SESSION_PREFIX}${companyId || 'global'}`;
const onceKey = (companyId, sessionId, key) => `${ONCE_PREFIX}${companyId || 'global'}:${sessionId}:${key}`;

export const getOrCreateOnboardingSession = (companyId) => {
    const sessionStorage = storage();
    const key = sessionKey(companyId);

    if (sessionStorage) {
        const existing = sessionStorage.getItem(key);
        if (existing) {
            try {
                const parsed = JSON.parse(existing);
                if (parsed?.id && parsed?.startedAt) {
                    return parsed;
                }
            } catch {
                sessionStorage.removeItem(key);
            }
        }
    }

    const created = {
        id: randomId(),
        startedAt: Date.now(),
    };

    if (sessionStorage) {
        sessionStorage.setItem(key, JSON.stringify(created));
    }

    return created;
};

const buildPayload = ({
    companyId,
    name,
    contextSurface,
    taskId = null,
    userSegment = 'novice',
    guidanceLevel = 'light',
    attemptIndex = 1,
    properties = {},
}) => {
    const session = getOrCreateOnboardingSession(companyId);
    const elapsed = Math.max(0, Math.round((Date.now() - Number(session.startedAt || Date.now())) / 1000));

    return {
        company_id: companyId || null,
        name,
        context_surface: contextSurface,
        task_id: taskId,
        user_segment: userSegment,
        guidance_level: guidanceLevel,
        session_id: session.id,
        attempt_index: attemptIndex,
        time_since_session_start_sec: elapsed,
        properties,
    };
};

export const trackOnboardingEvent = async ({
    companyId,
    name,
    contextSurface,
    taskId = null,
    userSegment = 'novice',
    guidanceLevel = 'light',
    attemptIndex = 1,
    properties = {},
    useKeepalive = false,
}) => {
    if (!name || !contextSurface) {
        return null;
    }

    const payload = buildPayload({
        companyId,
        name,
        contextSurface,
        taskId,
        userSegment,
        guidanceLevel,
        attemptIndex,
        properties,
    });

    if (useKeepalive && typeof window !== 'undefined' && typeof window.fetch === 'function') {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            await window.fetch('/onboarding/events', {
                method: 'POST',
                credentials: 'same-origin',
                keepalive: true,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            return payload;
        } catch {
            return null;
        }
    }

    try {
        await axios.post('/onboarding/events', payload);
        return payload;
    } catch {
        return null;
    }
};

export const trackOnboardingEventOnce = async ({
    onceId,
    ...attributes
}) => {
    const companyId = attributes.companyId;
    const session = getOrCreateOnboardingSession(companyId);
    const sessionStorage = storage();
    const key = onceKey(companyId, session.id, onceId || `${attributes.name}:${attributes.contextSurface}:${attributes.taskId || 'default'}`);

    if (sessionStorage?.getItem(key)) {
        return null;
    }

    sessionStorage?.setItem(key, '1');
    return trackOnboardingEvent(attributes);
};

export const ensureOnboardingSessionStarted = async ({
    companyId,
    contextSurface,
    taskId = 'company_activation',
    userSegment = 'novice',
    guidanceLevel = 'light',
}) => trackOnboardingEventOnce({
    onceId: `session_started:${contextSurface}:${taskId}`,
    companyId,
    name: 'session_started',
    contextSurface,
    taskId,
    userSegment,
    guidanceLevel,
});
