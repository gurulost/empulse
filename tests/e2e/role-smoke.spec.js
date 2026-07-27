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
