# Empulse Respondent Accessibility Experience Brief

Mode: original design
Gate: Tier 2
Scope: secure respondent promise and survey-taking flow

## Experience intent

An employee must be able to understand the confidentiality promise, enter the survey, recover from validation errors, answer every supported control, move between pages, observe save state, and recognize completion without requiring a pointer, animation, or visual-only cue.

## Required states

- loading, load failure, privacy promise, privacy acknowledgment failure;
- untouched, selected, invalid, saving, saved, autosave failure;
- page transition, previous/next navigation, submission, already completed, successful completion;
- desktop and narrow-phone layouts;
- reduced-motion preference.

## Release checks

- Semantic labels, descriptions, invalid state, errors, progress, and save state remain programmatically available.
- Privacy entry, first-error recovery, slider changes, and page navigation work from the keyboard.
- Focus moves to the active promise/page/completion heading and to the control that can resolve the first validation error.
- The real survey page, not only the promise screen, passes the repository axe gate.
- Widths at 375, 390, 768, 1440, and 1920 pixels have no unintended horizontal overflow or clipped primary controls.
- Reduced-motion preference removes survey transition motion without hiding state changes.
- Browser console and page errors remain empty through the tested respondent flow.

## Evidence boundary

Automated component, axe, keyboard, responsive, and browser-process checks reduce implementation risk. They do not replace independent keyboard and screen-reader review by an accountable accessibility reviewer before customer launch.
