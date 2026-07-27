import { expect, test } from '@playwright/test';

const expectHealthyPage = async (page, path, marker = null) => {
    const pageErrors = [];
    page.on('pageerror', (error) => pageErrors.push(error.message));

    const response = await page.goto(path);
    expect(response.status()).toBeLessThan(400);
    await page.waitForLoadState('networkidle');

    if (marker) {
        await expect(page.locator('body')).toContainText(marker);
    }

    const text = (await page.locator('body').innerText()).trim();
    expect(text.length).toBeGreaterThan(30);
    expect(pageErrors).toEqual([]);
};

const login = async (page, email, password = 'password') => {
    await page.goto('/login');
    await page.getByLabel('Email Address').fill(email);
    await page.getByLabel('Password').fill(password);
    await page.getByRole('button', { name: 'Log In' }).click();
    await page.waitForLoadState('networkidle');
};

const completePulseAssignment = async (page, email) => {
    await page.context().clearCookies();
    await login(page, email);
    await expectHealthyPage(page, '/employee', 'Current Assignment');

    const launchUrl = await page.getByRole('link', { name: 'Open Survey' }).getAttribute('href');
    const launchResponse = await page.goto(launchUrl);
    expect(launchResponse.status()).toBeLessThan(400);
    await page.getByLabel(/I have read this respondent data promise/).check();
    await page.getByRole('button', { name: 'Continue to survey' }).click();
    await expect(page.locator('body')).toContainText('3 questions');

    const answeredQids = new Set();
    while (true) {
        const sliders = page.locator('input[type="range"]:visible');
        const visibleQids = await page.locator('[data-qid]:visible').evaluateAll(
            (elements) => elements.map((element) => element.dataset.qid),
        );
        for (const qid of visibleQids) answeredQids.add(qid);
        await sliders.evaluateAll((elements) => {
            for (const slider of elements) {
                slider.value = String(Math.min(Number(slider.max), Number(slider.min) + 2));
                slider.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });

        const submit = page.getByRole('button', { name: 'Submit Survey' });
        if (await submit.isVisible()) {
            await submit.click();
            break;
        }
        await page.getByRole('button', { name: /^Next/ }).click();
        await page.waitForTimeout(250);
    }

    expect(answeredQids.size).toBe(3);
    await expect(page.getByRole('heading', { name: 'Thank you!' })).toBeVisible();
};

test('guest routes render without blank screens', async ({ page }) => {
    await expectHealthyPage(page, '/', 'Empulse');
    await expectHealthyPage(page, '/login', 'Log In');
    await expectHealthyPage(page, '/register', 'Create Account');
});

test('workfit admin routes render', async ({ page }) => {
    await login(page, 'admin@workfit.com');
    await expectHealthyPage(page, '/home', 'Companies');
    await expectHealthyPage(page, '/reports', 'Reports');
    await expectHealthyPage(page, '/admin', 'Companies');
    await expectHealthyPage(page, '/admin/builder', 'Survey Status');
});

test('workfit admin user list shows chief role label', async ({ page }) => {
    await login(page, 'admin@workfit.com');
    await page.goto('/admin');
    await page.waitForLoadState('networkidle');
    await page.getByRole('link', { name: 'Users' }).click();
    await page.waitForLoadState('networkidle');
    await page.getByPlaceholder('Search users...').fill('chief@acme.com');
    await page.getByPlaceholder('Search users...').press('Enter');
    await page.waitForLoadState('networkidle');
    await expect(page.locator('tbody')).toContainText('chief@acme.com');
    await expect(page.locator('tbody')).toContainText('Chief');
});

test('workfit admin onboarding tab renders', async ({ page }) => {
    await login(page, 'admin@workfit.com');
    await page.goto('/admin');
    await page.waitForLoadState('networkidle');
    await page.getByRole('link', { name: 'Onboarding' }).click();
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toContainText('Activation By Company');
    await expect(page.locator('body')).toContainText('Survey Content Status');
});

test('workfit admin audit explorer renders verified metadata without change payloads', async ({ page }) => {
    await login(page, 'admin@workfit.com');
    await page.goto('/admin');
    await page.waitForLoadState('networkidle');
    await page.getByRole('link', { name: 'Audit' }).click();
    await page.waitForLoadState('networkidle');
    await expect(page.getByRole('heading', { name: 'Audit event explorer' })).toBeVisible();
    await expect(page.locator('body')).toContainText('Integrity verified');
    await expect(page.getByRole('columnheader', { name: 'Action' })).toBeVisible();
});

test('workfit advisor queue renders only customer-authorized work metadata', async ({ page }) => {
    await login(page, 'admin@workfit.com');
    await page.goto('/admin');
    await page.waitForLoadState('networkidle');
    await page.getByRole('link', { name: 'Advisor Queue' }).click();
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toContainText('Only work for organizations that currently grant you advisor access is shown');
    await expect(page.getByRole('columnheader', { name: 'Evidence context' })).toBeVisible();
    await expect(page.locator('body')).toContainText('No authorized advisor work matches these filters');
});

test('manager routes render', async ({ page }) => {
    await login(page, 'manager@acme.com');
    await expectHealthyPage(page, '/home', 'Turn listening into accountable action');
    await expectHealthyPage(page, '/actions', 'Leadership action workspace');
    await expectHealthyPage(page, '/reports', 'Reports');
    await expectHealthyPage(page, '/team/manage');
    await expectHealthyPage(page, '/surveys/manage', 'Survey Overview');
    await expectHealthyPage(page, '/survey-waves', 'Existing Waves');
    await expectHealthyPage(page, '/account/billing', 'Account & Billing');
});

const runNorthStarJourney = async ({ page }) => {
    await login(page, 'manager@acme.com');
    await page.goto('/actions');
    await page.getByLabel('Completed wave').selectOption({ label: 'WorkFit Baseline — prior cycle' });
    await page.getByLabel('Metric ID').fill('opportunity.WCA_REL');
    await page.getByRole('button', { name: 'Verify and capture' }).click();

    await page.getByLabel(/Decision rationale/).fill(
        'Leadership will test a bounded relationship practice and review comparable follow-up evidence.',
    );
    await page.getByRole('button', { name: 'Accept for action' }).click();

    await page.getByLabel('Title', { exact: true }).fill('Protected peer connection practice');
    await page.getByLabel('Owner').selectOption({ label: 'Manager User' });
    await page.getByLabel('Hypothesis').fill(
        'Protected peer connection time may reduce the eligible cohort relationship gap.',
    );
    await page.getByLabel('Planned change').fill(
        'Run one facilitated peer connection session each week for six weeks.',
    );
    await page.getByLabel('Success criteria').fill(
        'Observe at least a 0.5 decrease in the relationship gap without violating privacy guardrails.',
    );
    await page.getByRole('button', { name: 'Create draft action' }).click();

    await page.getByLabel(/Expected direction/).selectOption('decrease');
    await page.getByLabel(/Minimum change/).fill('0.5');
    await page.getByRole('button', { name: 'Plan follow-up' }).click();

    const yesterday = new Date(Date.now() - 86400000).toISOString().slice(0, 10);
    const inTwoWeeks = new Date(Date.now() + 14 * 86400000).toISOString().slice(0, 10);
    await page.getByLabel(/Governed Pulse label/).fill('E2E governed relationship follow-up');
    await page.getByLabel(/^Opens$/).fill(yesterday);
    await page.getByLabel(/^Due$/).fill(inTwoWeeks);
    await page.getByRole('button', { name: 'Create', exact: true }).click();

    await page.getByRole('button', { name: 'Commit with this measurement plan' }).click();
    await page.getByLabel(/Employee follow-through message/).fill(
        'We heard a need for stronger peer connection, will test protected weekly time, and will report what the eligible follow-up evidence shows.',
    );
    await page.getByRole('button', { name: 'Record as published' }).click();
    await expect(page.locator('body')).toContainText('committed');

    await page.goto('/survey-waves');
    const followupRow = page.locator('tr', { hasText: 'E2E governed relationship follow-up' }).first();
    await followupRow.getByTitle('Run now').click();
    await expect.poll(async () => {
        const response = await page.request.get('/survey-waves');
        return response.ok() ? response.text() : '';
    }, { timeout: 30000 }).toContain('Sent 10/10');
    await page.reload();
    await expect(page.locator('tr', { hasText: 'E2E governed relationship follow-up' }).first())
        .toContainText('Sent 10/10');

    for (let employee = 6; employee <= 10; employee += 1) {
        await completePulseAssignment(page, `employee${employee}@acme.com`);
    }

    await page.context().clearCookies();
    await login(page, 'manager@acme.com');
    await page.goto('/actions');
    await page.getByRole('button', { name: 'Evaluate available follow-up evidence' }).click();
    await expect(page.locator('body')).toContainText('Recorded outcome');
    await expect(page.locator('body')).toContainText('Comparable definition');
    await expect(page.locator('body')).toContainText('Yes');
    await expect(page.locator('body')).toContainText('does not establish');

    await page.context().clearCookies();
    await login(page, 'admin@workfit.com');
    await page.goto('/admin');
    await page.getByRole('link', { name: 'Value Loop' }).click();
    await expect(page.locator('body')).toContainText('Reliable findings → action');
    await expect(page.locator('body')).toContainText('Reliable findings → measured outcome');
    await expect(page.locator('body')).toContainText('100%');
};

test('manager sees governed roster import while non-managers do not', async ({ page }) => {
    await login(page, 'manager@acme.com');
    await page.goto('/team/manage');
    await page.waitForLoadState('networkidle');
    await page.getByRole('button', { name: 'Import CSV' }).click();
    await expect(page.getByRole('heading', { name: 'Governed roster import' })).toBeVisible();
    await expect(page.locator('body')).toContainText('No roster data changes during preview');
    await expect(page.getByLabel('Roster CSV')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Commit Reviewed Changes' })).toHaveCount(0);

    await page.context().clearCookies();
    await login(page, 'chief@acme.com');
    await page.goto('/team/manage');
    await page.waitForLoadState('networkidle');
    await expect(page.getByRole('button', { name: 'Import CSV' })).toHaveCount(0);
});

test('authorization and survey-token failures fail closed', async ({ page }) => {
    const invalidSurvey = await page.goto('/survey/not-a-valid-assignment-token');
    expect(invalidSurvey.status()).toBe(404);

    await login(page, 'employee1@acme.com');
    await page.goto('/admin');
    await expect(page).not.toHaveURL(/\/admin$/);
});

test('chief routes render', async ({ page }) => {
    await login(page, 'chief@acme.com');
    await expectHealthyPage(page, '/home', 'Turn listening into accountable action');
    await expectHealthyPage(page, '/reports', 'Reports');
    await expectHealthyPage(page, '/team/manage');
});

test('team lead routes render', async ({ page }) => {
    await login(page, 'lead@acme.com');
    await expectHealthyPage(page, '/home', 'Turn listening into accountable action');
    await expectHealthyPage(page, '/reports', 'Reports');
    await expectHealthyPage(page, '/team/manage');
});

test('employee dashboard renders', async ({ page }) => {
    await login(page, 'employee3@acme.com');
    await expectHealthyPage(page, '/employee', 'Employee Dashboard');
    await expect(page.locator('body')).toContainText('Before you start');
    await expect(page.locator('body')).toContainText('Progress autosaves');
});

test('employee acknowledges the data promise and completes an assignment', async ({ page }) => {
    await login(page, 'employee4@acme.com');
    await expectHealthyPage(page, '/employee', 'Current Assignment');

    const launchUrl = await page.getByRole('link', { name: 'Open Survey' }).getAttribute('href');
    const launchResponse = await page.goto(launchUrl);
    expect(launchResponse.status()).toBeLessThan(400);

    await expect(page.locator('body')).toContainText('Empulse respondent data promise');
    await page.getByLabel(/I have read this respondent data promise/).check();
    await page.getByRole('button', { name: 'Continue to survey' }).click();
    await expect(page.locator('body')).toContainText('Progress autosaves');
    await expect(page.locator('body')).toContainText('62 questions');

    await page.getByRole('button', { name: /^Next/ }).click();
    expect(await page.getByText('Please provide an answer.').count()).toBeGreaterThan(0);

    const answeredQids = new Set();
    while (true) {
        const sliders = page.locator('input[type="range"]:visible');
        const sliderCount = await sliders.count();
        const visibleQids = await page.locator('[data-qid]:visible').evaluateAll(
            (elements) => elements.map((element) => element.dataset.qid),
        );
        for (const qid of visibleQids) {
            answeredQids.add(qid);
        }
        await sliders.evaluateAll((elements) => {
            for (const slider of elements) {
                slider.value = String(Math.min(Number(slider.max), Number(slider.min) + 1));
                slider.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
        await expect(page.locator('.badge:visible', { hasText: 'Selected:' })).toHaveCount(sliderCount);

        const submit = page.getByRole('button', { name: 'Submit Survey' });
        if (await submit.isVisible()) {
            await submit.click();
            break;
        }
        const progress = page.getByText(/^Page \d+ of \d+$/);
        const previousProgress = await progress.innerText();
        await page.getByRole('button', { name: /^Next/ }).click();
        await expect(progress).not.toHaveText(previousProgress);
        await page.waitForTimeout(350);
    }
    expect(answeredQids.size).toBe(62);
    await expect(page.getByRole('heading', { name: 'Thank you!' })).toBeVisible();

    await expectHealthyPage(page, '/employee', 'No survey is assigned right now');
    await expect(page.locator('body')).toContainText('WorkFit Baseline — current cycle');
    await expect(page.locator('body')).toContainText('Completed');
});

test(
    'north-star finding to action to governed remeasurement works through the product',
    runNorthStarJourney,
);
