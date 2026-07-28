import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

const login = async (page, email) => {
    await page.goto('/login');
    await page.getByLabel('Email Address').fill(email);
    await page.getByLabel('Password').fill('password');
    await page.getByRole('button', { name: 'Log In' }).click();
    await page.waitForLoadState('networkidle');
};

const expectNoSeriousAccessibilityViolations = async (page) => {
    await page.waitForLoadState('networkidle');
    const results = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
        .analyze();
    const violations = results.violations
        .filter((violation) => ['serious', 'critical'].includes(violation.impact))
        .map((violation) => ({
            id: violation.id,
            impact: violation.impact,
            help: violation.help,
            nodes: violation.nodes.map((node) => ({
                target: node.target.join(' '),
                html: node.html,
                failureSummary: node.failureSummary,
            })),
        }));

    expect(
        violations,
        `Serious accessibility violations:\n${JSON.stringify(violations, null, 2)}`,
    ).toEqual([]);
};

test('public and login pages have no serious automated WCAG violations', async ({ page }) => {
    await page.goto('/');
    await expectNoSeriousAccessibilityViolations(page);

    await page.goto('/login');
    await expectNoSeriousAccessibilityViolations(page);
});

test('manager operating loop and action workspace have no serious automated WCAG violations', async ({ page }) => {
    await login(page, 'manager@acme.com');

    await page.goto('/home');
    await expectNoSeriousAccessibilityViolations(page);

    await page.goto('/actions');
    await expectNoSeriousAccessibilityViolations(page);
});

test('respondent promise and real survey support axe, keyboard recovery, reduced motion, and narrow widths', async ({ page }) => {
    const browserErrors = [];
    page.on('pageerror', (error) => browserErrors.push(error.message));
    page.on('console', (message) => {
        if (message.type() === 'error') browserErrors.push(message.text());
    });
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await login(page, 'employee5@acme.com');

    await page.goto('/employee');
    await expectNoSeriousAccessibilityViolations(page);

    const launchUrl = await page.getByRole('link', { name: 'Open Survey' }).getAttribute('href');
    await page.goto(launchUrl);
    await expect(page.locator('body')).toContainText('Empulse respondent data promise');
    await expect(page.getByRole('heading', { name: 'Empulse respondent data promise' })).toBeFocused();
    await expectNoSeriousAccessibilityViolations(page);

    const privacyCheckbox = page.getByLabel(/I have read this respondent data promise/);
    await privacyCheckbox.focus();
    await expect(privacyCheckbox).toBeFocused();
    await page.keyboard.press('Space');
    await page.keyboard.press('Tab');
    await expect(page.getByRole('button', { name: 'Continue to survey' })).toBeFocused();
    await page.keyboard.press('Enter');

    const pageHeading = page.locator('h1[tabindex="-1"]');
    await expect(pageHeading).toBeFocused();
    await expect(page.locator('body')).toContainText('62 questions');
    await expect(page.locator('.survey-container')).toHaveCSS('--survey-fade-duration', '0s');
    await expectNoSeriousAccessibilityViolations(page);

    const nextButton = page.getByRole('button', { name: /^Next/ });
    await nextButton.focus();
    await page.keyboard.press('Enter');

    const firstSlider = page.locator('input[type="range"]:visible').first();
    await expect(firstSlider).toBeFocused();
    await expect(firstSlider).toHaveAttribute('aria-invalid', 'true');
    await expect(firstSlider).toHaveAttribute('aria-errormessage', /-error$/);
    await expect(firstSlider).toHaveAttribute('aria-describedby', /scale-description/);
    await expect(page.getByText('Please provide an answer.').first()).toBeVisible();

    const sliders = page.locator('input[type="range"]:visible');
    const sliderCount = await sliders.count();
    for (let index = 0; index < sliderCount; index += 1) {
        const slider = sliders.nth(index);
        await slider.focus();
        await page.keyboard.press('ArrowRight');
        await expect(slider).toHaveAttribute('aria-valuetext', /^Selected /);
    }

    await expect(page.getByText(/^Saved /)).toBeVisible();
    for (const width of [375, 390, 768, 1440, 1920]) {
        await page.setViewportSize({ width, height: width < 768 ? 812 : 1000 });
        await expect(nextButton).toBeVisible();
        const hasHorizontalOverflow = await page.evaluate(
            () => document.documentElement.scrollWidth > document.documentElement.clientWidth,
        );
        expect(hasHorizontalOverflow, `Unexpected horizontal overflow at ${width}px`).toBe(false);
        const buttonBox = await nextButton.boundingBox();
        expect(buttonBox.height).toBeGreaterThanOrEqual(44);
        expect(buttonBox.x).toBeGreaterThanOrEqual(0);
        expect(buttonBox.x + buttonBox.width).toBeLessThanOrEqual(width + 1);
    }

    await nextButton.focus();
    await page.keyboard.press('Enter');
    await expect(pageHeading).toBeFocused();
    await expect(page.getByText(/^Page 2 of \d+$/)).toBeVisible();
    expect(browserErrors).toEqual([]);
});
