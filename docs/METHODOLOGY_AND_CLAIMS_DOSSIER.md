# Empulse WorkFit Methodology and Claims Dossier

Status: release-candidate methodology contract, version 1.0.0.

This dossier defines what Empulse may calculate and say. It deliberately does not claim that the current instrument is a validated psychometric, clinical, causal, or benchmarking instrument. Until an independent research review supplies stronger evidence, results are descriptive operational indices used to structure leadership inquiry and follow-through.

## Intended use

Empulse helps a small-to-mid-sized organization identify recurring gaps between employees’ current and desired work experience, examine team-culture signals, choose a leadership response, and evaluate later movement without claiming that the action caused the movement.

It must not be used for employment selection, individual performance evaluation, diagnosis, compensation decisions, disciplinary action, or ranking an identifiable employee. Individual responses are not a customer-facing product surface.

## Baseline instrument 2.0.0

The canonical `empulse_workfit_baseline` contains 62 questions:

- 36 Work Content Attribute items: current, ideal, and desire for 12 actionable attributes;
- 20 team-culture and workplace-leadership items used in three explicitly labeled exploratory operational indices;
- 6 impact-on-society items used as descriptive current, importance, and desire signals.

Version 2.0.0 intentionally removes:

- four contact fields already supplied by secure roster identity;
- sixteen organizational-culture items that had no approved analytical or action use;
- sixteen demographic items whose privacy and re-identification cost was not justified by the initial product.

Every retained QID appears in the versioned metric registry or in a documented opportunity input. Instrument publication fails if the canonical metric registry references a missing QID.

## Scoring order

Scoring is respondent-first:

1. Validate and normalize each submitted item.
2. Calculate the respondent’s attribute and scale values only when that scale meets its valid-item rule.
3. Reverse-code the registry’s negative culture items.
4. Aggregate respondent scores into the eligible cohort.
5. Return valid N, invited N, completion, and missingness with the result.

Pooling all answer rows before respondent scoring is prohibited because partial responders would otherwise receive unintended weight.

### Work Content Attributes

For each attribute:

- `current` is the respondent’s A item;
- `ideal` is the respondent’s B item;
- `desire` is the respondent’s C item;
- `gap = ideal - current`.

Indicator satisfaction is calculated only when all attributes assigned to that indicator have valid current and ideal values:

`satisfaction = clamp(mean(current) / mean(ideal) × 10, 0, 10)`

If mean ideal is zero or unavailable, the metric is unavailable. A value of 10 means current meets or exceeds the selected ideal; it is not “100% employee satisfaction” and must not be labeled as such.

The weighted indicator is the weighted mean of available respondent indicator scores using the frozen registry weights. Current weights are equal and are a transparent product choice, not an empirically estimated model.

### Team culture

Negative items are reversed on the 1–9 scale with `reversed = 10 - value`. Each dimension requires at least 80% of its assigned items for an individual score. Dimension weights are frozen in the metric registry.

Cronbach’s alpha is reported as a provisional internal-consistency diagnostic only when at least 10 complete cases exist. Threshold labels are:

- 0.70 or higher: acceptable;
- 0.60–0.69: caution;
- below 0.60: low;
- fewer than 10 complete cases or zero total variance: indeterminate.

These labels do not establish validity. A low or indeterminate value must be disclosed and reviewed before the index is used to prioritize action.

### Temperature index

The temperature index combines the weighted Work Content indicator and culture index after normalizing culture to 0–10. Initial weights are 65% Work Content and 35% culture. It is an operational summary, not a validated latent construct, and cannot be the sole basis for a recommendation.

### Opportunity priority

The product may sort opportunities using transparent inputs—gap magnitude, stated desire/importance, reliability, and affected cohort size—but must show those components. It must not present a hidden or AI-generated causal ranking. Version 1 uses descriptive gap ordering until sensitivity review approves a composite priority formula.

## Sample and privacy policy

Results fail closed unless all applicable conditions are met:

- company cohort: at least 5 valid respondents;
- department/team cohort: at least 7 valid respondents;
- completion rate: at least 40%;
- a subgroup is hidden if it does not meet the threshold;
- if only one subgroup is hidden, the smallest otherwise-visible subgroup is also hidden to prevent subtraction;
- filters, comparisons, trends, exports, and advisor views use the same gate.

“Valid N” counts respondents with a valid score for the displayed metric, not merely submitted assignments. Each metric carries item/scale missingness. An organization-level gate does not override a stricter metric-specific valid N.

## Longitudinal comparability

A trend point is comparable only when:

- the instrument semantic hash matches;
- the metric-registry definition hash matches;
- the cohort definition and frozen audience are disclosed;
- the point independently passes sample and completion rules.

Organization-unit names and reporting relationships come from the frozen response cohort, not the current roster. Incompatible points are shown as unavailable with a reason; they are never connected into one continuous trend.

## Interpretation contract

Every result must answer:

- what was measured;
- which wave, cohort, instrument, and metric version produced it;
- invited N, submitted N, valid N, completion, and missingness;
- whether the result is eligible, suppressed, incompatible, or reliability-limited;
- what leadership question it raises;
- what the data cannot establish;
- which recorded action and future measurement could test progress.

Allowed language: “respondents reported,” “the eligible cohort’s descriptive average,” “this pattern suggests a question to investigate,” and “movement coincided with the recorded action.”

Prohibited language without new evidence: “proves,” “caused,” “scientifically validated,” “anonymous,” “industry benchmark,” “predicts retention,” or “employees are satisfied” based solely on the ratio index.

## Governance

Instrument and metric changes require:

1. a draft version;
2. schema and registry compatibility lint;
3. purpose, privacy, burden, and claims review;
4. semantic content and metric hashes;
5. explicit publication by an authorized WorkFit operator;
6. a comparability decision against prior versions.

Historical responses and wave cycles retain their original instrument and metric hashes. Code or configuration changes cannot reinterpret a frozen wave silently.
