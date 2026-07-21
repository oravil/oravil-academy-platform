<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds real MVP content (Phase 0, Module 1 of the Digital Marketing learning
 * path) per SPRINT-001's Content Seeding step and OA-MVP-004/006. This is not
 * dev-convenience data — see DevLearnerSeeder for that.
 *
 * Lesson content is embedded verbatim (not read from the docs repo at runtime)
 * because this repo and the docs repo (oravil-academy) never mix at the
 * filesystem level (ADR-0002) and CI/Docker only check out this repo. Source
 * paths are cited per block below for traceability back to the docs repo.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $learningPathId = $this->firstOrInsertId('learning_paths',
            ['slug' => 'digital-marketing'],
            ['title' => 'Digital Marketing']
        );

        $phaseId = $this->firstOrInsertId('phases',
            ['learning_path_id' => $learningPathId, 'slug' => 'phase-0-foundations'],
            ['title' => 'Phase 0 — Foundations', 'position' => 1]
        );

        // Title and deliverable_description: academy/learning-paths/digital-marketing/phase-0/module-1/MODULE_BRIEF.md
        $moduleId = $this->firstOrInsertId('modules',
            ['phase_id' => $phaseId, 'slug' => 'module-1'],
            [
                'title' => 'The Digital Marketing Landscape',
                'position' => 1,
                'deliverable_description' => 'A completed Digital Marketing Landscape Map: a structured document in which the learner categorizes the core channels, identifies at least two differentiating factors between digital and traditional marketing, and maps three professional roles to their primary responsibilities.',
            ]
        );

        foreach ($this->lessons() as $position => $lesson) {
            $lessonId = $this->firstOrInsertId('lessons',
                ['module_id' => $moduleId, 'slug' => $lesson['slug']],
                [
                    'title' => $lesson['title'],
                    'position' => $position + 1,
                    'content' => $lesson['content'],
                ]
            );

            $this->firstOrInsertId('assignments',
                ['lesson_id' => $lessonId],
                [
                    'prompt' => $lesson['assignment_prompt'],
                    'deliverable_name' => $lesson['assignment_deliverable_name'],
                    'minimum_word_count' => null,
                ]
            );
        }

        // Question text: docs repo academy/../MVP_WIREFRAMES.md (OA-MVP-004), Screen 5 — Post-Module Survey
        $surveyId = $this->firstOrInsertId('surveys',
            ['module_id' => $moduleId],
            ['title' => 'Post-Module Survey']
        );

        $questions = [
            ['question_text' => 'How would you rate your learning experience in Module 1?', 'question_type' => 'rating', 'required' => true],
            ['question_text' => 'What would you change or improve about this module?', 'question_type' => 'text', 'required' => true],
            ['question_text' => 'Is there anything else you want to share?', 'question_type' => 'text', 'required' => false],
        ];

        foreach ($questions as $i => $question) {
            $this->firstOrInsertId('survey_questions',
                ['survey_id' => $surveyId, 'position' => $i + 1],
                [
                    'question_text' => $question['question_text'],
                    'question_type' => $question['question_type'],
                    'required' => $question['required'],
                ]
            );
        }
    }

    /**
     * Finds a row by $match and returns its id; otherwise inserts $match + $attributes
     * (plus a fresh id and timestamps) and returns the new id. Deliberately not
     * Eloquent's firstOrCreate()/updateOrInsert(): both merge two arrays under the
     * hood (create-path clobbering, or unconditional timestamp rewrite on match),
     * which breaks "identical state across repeated seeder runs". This insert-only
     * path exercises cleanly on both branches with no hidden merge order.
     */
    private function firstOrInsertId(string $table, array $match, array $attributes): string
    {
        $existing = DB::table($table)->where($match)->value('id');

        if ($existing !== null) {
            return $existing;
        }

        $id = (string) Str::uuid();
        $now = now();

        DB::table($table)->insert(array_merge($match, $attributes, [
            'id' => $id,
            'created_at' => $now,
            'updated_at' => $now,
        ]));

        return $id;
    }

    /**
     * @return array<int, array{slug: string, title: string, content: string, assignment_prompt: string, assignment_deliverable_name: string}>
     */
    private function lessons(): array
    {
        return [
            [
                'slug' => 'what-is-digital-marketing',
                'title' => 'What Is Digital Marketing',
                'content' => $this->lesson1Content(),
                // Source: lesson-01-brief.md ## Deliverable (lesson-01-what-is-digital-marketing.md has no
                // explicit ## Deliverable section — its "## Assignment" section is a separate, unrelated
                // practice exercise about a fitness-studio client, not the Landscape Map deliverable).
                'assignment_prompt' => 'A written definition of digital marketing (maximum 75 words) produced by the learner without reference material, submitted as part of the Module 1 Landscape Map.',
                // Source: lesson-01-what-is-digital-marketing.md, Hands-on Workshop, Step 1 (section title, verbatim)
                'assignment_deliverable_name' => 'My Definition of Digital Marketing',
            ],
            [
                'slug' => 'digital-vs-traditional-marketing',
                'title' => 'Digital vs. Traditional Marketing',
                'content' => $this->lesson2Content(),
                // Source: lesson-02-digital-vs-traditional-marketing.md ## Deliverable
                'assignment_prompt' => 'A completed comparison table in your Module 1 Landscape Map contrasting digital and traditional marketing across at least five dimensions, plus a written recommendation of 2–3 sentences for the fitness studio client identifying the appropriate channel approach and the reasoning behind it.',
                // Source: lesson-02-digital-vs-traditional-marketing.md, Hands-on Workshop, Step 2 (section title, verbatim)
                'assignment_deliverable_name' => 'Digital vs. Traditional Marketing: Comparison Table',
            ],
            [
                'slug' => 'core-digital-marketing-channels',
                'title' => 'Core Digital Marketing Channels',
                'content' => $this->lesson3Content(),
                // Source: lesson-03-core-digital-marketing-channels.md ## Deliverable
                'assignment_prompt' => 'A channel classification table in the Module 1 Landscape Map listing all six channel categories, their reach mechanism, and their primary business purpose — written in the learner\'s own words — plus a written channel recommendation of 2–3 sentences for the meal kit company scenario from the Business Context.',
                // Source: lesson-03-core-digital-marketing-channels.md, Hands-on Workshop, Step 2 (section title, verbatim)
                'assignment_deliverable_name' => 'Digital Marketing Channel Categories',
            ],
            [
                'slug' => 'digital-marketing-careers',
                'title' => 'Digital Marketing Careers',
                'content' => $this->lesson4Content(),
                // Source: lesson-04-digital-marketing-careers.md ## Deliverable
                'assignment_prompt' => 'A Digital Marketing Team Map added to the Module 1 Landscape Map, listing at least five major digital marketing roles, the primary responsibility of each, and at least two clearly described collaboration relationships between roles — written in the learner\'s own words.',
                // Source: lesson-04-digital-marketing-careers.md, Hands-on Workshop, Step 2 (section title, verbatim)
                'assignment_deliverable_name' => 'Digital Marketing Team Map',
            ],
        ];
    }

    // Source: academy/learning-paths/digital-marketing/phase-0/module-1/lesson-01-what-is-digital-marketing.md
    private function lesson1Content(): string
    {
        return <<<'MARKDOWN'
# What Is Digital Marketing

---

## Lesson Information

| Field | Value |
|---|---|
| **Learning Path** | Digital Marketing |
| **Phase** | Phase 0 — Foundations |
| **Module** | Module 1 — The Digital Marketing Landscape |
| **Lesson** | 1 of 4 |
| **Difficulty** | Beginner |
| **Estimated Duration** | 30–40 minutes |
| **Prerequisites** | None |

---

## Learning Objectives

By the end of this lesson, you will be able to:

- State a professional definition of digital marketing.
- Identify what makes digital marketing measurable.
- Explain the relationship between business objectives and digital channels.
- Use the terms owned media, earned media, and paid media correctly.

---

## Business Context

You have just joined a marketing team. Your manager hands you a brief and says: "We need to increase sign-ups by 20% this quarter using digital."

You open the document. You see a budget, a launch date, and a list of channels: search, social, email, display.

Your manager then asks: "Which of these would you prioritize?"

Before you can answer that question well, you need to answer a simpler one: what is digital marketing, and how do the pieces fit together?

This is not a philosophical question. It is an operational one. Practitioners who cannot answer it clearly will select channels arbitrarily, write briefs imprecisely, and report on activity instead of results.

This lesson gives you a working answer — one that holds up in a client meeting, a team discussion, and every brief you will ever read.

---

## Core Concepts

### Start With the Question, Not the Definition

Most people, when asked to define digital marketing, describe a channel: "It's social media." Or a platform: "It's Google Ads." Or an activity: "It's running campaigns online."

None of those are wrong. But none of them are the definition.

Think about it differently. A business hires a digital marketer to achieve something — more customers, lower acquisition costs, higher retention. The channels are how that gets done. They are not what digital marketing is.

Once you see that, the definition becomes clear.

---

### The Definition

**Digital marketing** is the use of digital channels and technologies to reach defined audiences and achieve measurable business objectives.

Three elements in that definition deserve attention:

**Digital channels** are the delivery mechanisms — search engines, social platforms, email, websites, display networks. There are several of them, and each serves a different purpose. You will study each one in detail in later phases. For now, treat them as a set of tools, not the point of the work.

**Defined audiences** are the specific people the marketing is designed to reach. How audiences are defined and researched is covered in Module 5. What matters here is the sequence: the audience is identified before the channel is selected. Never the other way around.

**Measurable business objectives** are what the marketing is paid to achieve. Not "more engagement." Not "a stronger brand presence online." A business objective is specific and verifiable: increase trial sign-ups by 20%, reduce cost per acquisition to under £15, recover 10% of lapsed customers within one quarter.

If an activity cannot be traced to a business objective, it is not digital marketing. It is activity.

---

### Digital Marketing as a System

The channels are not independent. They work together.

A person discovers a brand through a paid ad. They visit the company's website. They read a blog post. Two weeks later, they receive an email. They return and convert.

That journey crossed paid media, owned media, and earned media. Each channel played a role at a different stage. No single channel produced the result — the system did.

How to design and manage that system is the subject of Phase 5. What you need now is the mental model: digital marketing is a system of connected channels, not a list of separate activities.

---

### The Three Media Types

All digital marketing activity belongs to one of three ownership categories.

**Owned media** — Channels the organization controls directly: the website, the email list, the app, the blog. There is no cost per impression, but building and maintaining these assets requires sustained investment.

**Earned media** — Exposure created by third parties without payment: press coverage, inbound links, social shares, customer reviews. It cannot be bought directly. It is the result of doing work that other people consider worth sharing or referencing.

**Paid media** — Advertising inventory purchased to reach a specific audience: search ads, social ads, display placements, sponsored content. It provides scale and targeting control in exchange for budget.

Professional practitioners plan across all three. A program built entirely on paid media is expensive and fragile. A program that develops owned and earned media assets reduces long-term cost and increases resilience.

---

### Measurability

This is the characteristic that makes digital marketing operationally different from most traditional marketing.

Every user action in a digital environment produces data. A page view, a click, a form submission, a purchase — each is recorded. This data is available continuously and can be analyzed while a campaign is running, not only after it ends.

The practical effect: budget can be reallocated in real time. Underperforming activity can be stopped. Successful activity can be scaled. Decisions are made on evidence, not assumption.

This is why organizations invest in digital marketing. Not because it costs less — it does not always cost less. Because performance is visible and can be tied directly to the business objectives the budget was allocated to achieve.

---

## Professional Thinking

| Beginner Thinking | Professional Thinking |
|---|---|
| "We should be on all channels." | "Which channels serve this objective and this audience?" |
| "Digital marketing means social media." | "Social media is one channel. Digital marketing is the system." |
| "We'll measure results at the end." | "KPIs are defined before the campaign begins." |
| "More content means more results." | "Output is not a KPI. Business outcomes are." |
| "We need to go viral." | "We need to hit the target cost-per-acquisition." |

The shift from left to right is not about learning more tools. It is about changing the frame of reference from activity to outcomes.

Beginners ask: what should we do?

Professionals ask: what are we trying to achieve, and how will we know if we have?

---

## Inside Oravil

Before a new team member at Oravil touches a client account, they are required to demonstrate a clear understanding of what digital marketing is and how it functions.

The reason is practical.

Client briefs are often written loosely. A brief that says "improve our digital presence" could mean ten different things. A practitioner who cannot anchor that phrase to a precise definition will interpret it differently every time — and so will the client.

Oravil's internal standard is that every channel recommendation must be linked to a stated business objective. Every campaign must have defined KPIs before work begins. Every report must answer the question: did we achieve the objective?

These standards only hold if every team member starts from the same definition of what the discipline is.

This lesson is where that begins.

---

## Practical Example

A software company offers a free trial of their project management tool. Their goal: increase free trial sign-ups by 25% over the next quarter without increasing the cost per sign-up.

Their digital marketing program includes:

- Search ads targeting people actively searching for project management tools. (Paid)
- A content blog that ranks for relevant search terms over time. (Owned)
- Email sequences that nurture leads who signed up but did not convert to a paid plan. (Owned)
- Press coverage from a product review by an industry publication. (Earned)

Each channel exists because it serves the objective. The search ads generate immediate volume. The blog reduces long-term cost. The email sequences increase conversion. The press coverage builds credibility.

No channel was chosen first. The objective determined the channel mix. That sequence — objective first, channels second — is the practice this lesson establishes.

---

## Hands-on Workshop

This workshop is the first entry in your Module 1 Landscape Map — a document you will build incrementally across all four lessons in this module.

**Task: Write and Test Your Definition**

Step 1. Open a blank document. Create a section titled: **My Definition of Digital Marketing**.

Step 2. Without referring to any notes, write your current understanding of digital marketing in 2–3 sentences. Do not aim for the perfect answer — write what you actually understand right now.

Step 3. Test your draft against these four criteria:

| Criterion | Does your definition pass? |
|---|---|
| Refers to channels as means, not the goal | Yes / No |
| Mentions business objectives | Yes / No |
| Indicates that results can be measured | Yes / No |
| Is broad enough to cover more than one channel | Yes / No |

Step 4. Revise until your definition passes all four. Maximum 75 words.

Step 5. Save the document. You will return to it in each of the next three lessons.

---

## Assignment

You are preparing for your first client meeting. The client runs a local fitness studio and currently promotes the business through a monthly ad in a regional lifestyle magazine and a board outside the building.

Before the meeting, your manager asks you to write a short internal note — not for the client — that answers this question:

**What would digital marketing give this client that their current activity cannot?**

Your note must be 150–200 words and must:

- Identify at least one structural limitation of the current approach.
- Name one specific capability digital marketing provides that addresses that limitation.
- Reference at least two terms from this lesson accurately.
- Be written in professional, direct language — not a sales pitch.

This note is for a colleague, not the client. Write accordingly.

---

## Common Mistakes

**Treating digital marketing as a single activity.**
Digital marketing is a system. Calling it "doing social media" or "running ads" reduces it to one component and makes everything else invisible.

**Selecting channels before defining objectives.**
Channels are answers to a question. The question is: what does the business need to achieve, and who needs to be reached? Without that question, channel selection is guesswork.

**Measuring activity instead of outcomes.**
Posts published, emails sent, and ads running are not results. Results are conversions, revenue, leads, and retention — outcomes the business cares about.

**Conflating owned, earned, and paid media.**
These are structurally different. Mixing them up leads to inaccurate budget planning and unrealistic expectations about what each type can produce.

**Assuming measurability means certainty.**
Data shows what happened. It does not always explain why. Measurement informs decisions — it does not replace judgment.

---

## Troubleshooting

**"I understand the definition, but I still find it hard to explain what digital marketing is."**
Practice the one-sentence version: "Digital marketing is the use of digital channels to reach specific audiences and achieve measurable business objectives." That sentence answers the question. Build from it.

**"I'm not sure how to classify a channel as owned, earned, or paid."**
Ask three questions in sequence. Did we build and control it? Owned. Did someone else create or distribute it without us paying them? Earned. Did we pay for the placement or reach? Paid.

**"This lesson feels abstract. When does it become practical?"**
The practice builds in every lesson that follows. This lesson establishes the frame. The rest of the learning path fills it in.

---

## Knowledge Check

Answer the following without reviewing the lesson. Write your responses before checking anything.

**1.** Write your definition of digital marketing in one sentence.

**2.** A colleague says: "We should put everything into Instagram — that's where digital marketing happens." Identify the specific error in that statement and correct it in two sentences.

**3.** A company runs search ads, publishes a weekly blog, and is mentioned in a trade publication. Classify each as owned, earned, or paid — and explain the classification rule you applied.

**4.** A client asks you: "Is digital marketing just online advertising?" How do you answer in three sentences or fewer?

**5.** Why does measurability matter to the business, not just to the marketing team?

---

## Lesson Summary

Digital marketing is the use of digital channels and technologies to reach defined audiences and achieve measurable business objectives.

It is a system. The channels are tools within that system — not the purpose of it.

All activity falls into one of three media types: owned (controlled by the organization), earned (generated by third parties), or paid (purchased reach). Effective programs use all three.

Measurability is the operational characteristic that defines the discipline. Every interaction produces data, and that data supports decisions throughout the work — not only at the end.

The professional sequence: define the objective, identify the audience, select the channels, set the KPIs — in that order, before execution begins.

Lesson 2 examines how digital marketing compares structurally to traditional marketing, and what that means when a client is running both.

---

## Additional Resources

- Google Digital Garage — [Fundamentals of Digital Marketing](https://learndigital.withgoogle.com/digitalgarage/course/digital-marketing)
- HubSpot Academy — [Digital Marketing Certification](https://academy.hubspot.com/courses/digital-marketing)
- American Marketing Association — [Definition of Marketing](https://www.ama.org/the-definition-of-marketing-what-is-marketing/)

---

## Next Lesson

**Lesson 2 — Digital vs. Traditional Marketing**

You now have a working definition of digital marketing. The next question is: how does it compare to traditional marketing — and when does each approach make sense?

Lesson 2 examines the structural differences between digital and traditional marketing: how they reach audiences differently, how their measurement models differ, and how professional practitioners think about both when a client is running both simultaneously.
MARKDOWN;
    }

    // Source: academy/learning-paths/digital-marketing/phase-0/module-1/lesson-02-digital-vs-traditional-marketing.md
    private function lesson2Content(): string
    {
        return <<<'MARKDOWN'
# Digital vs. Traditional Marketing

---

## Lesson Information

| Field | Value |
|---|---|
| **Learning Path** | Digital Marketing |
| **Phase** | Phase 0 — Foundations |
| **Module** | Module 1 — The Digital Marketing Landscape |
| **Lesson** | 2 of 4 |
| **Difficulty** | Beginner |
| **Estimated Duration** | 30–40 minutes |
| **Prerequisites** | Lesson 1 — What Is Digital Marketing |

---

## Learning Objectives

By the end of this lesson, you will be able to:

- Identify the structural differences between digital and traditional marketing.
- Explain the measurement advantage digital marketing holds over traditional marketing.
- Describe scenarios in which traditional marketing is the stronger choice.
- Describe scenarios in which digital marketing is the stronger choice.
- Articulate how both approaches can coexist within a single marketing strategy.

---

## Business Context

A regional healthcare provider wants to run a public health awareness campaign. Their marketing manager presents a plan that includes radio spots, posters in GP waiting rooms, and a newspaper feature.

The new digital marketing hire — you — is asked to review the plan before it is approved.

Your manager does not ask you to replace the plan. She asks a more precise question: "Is this the right mix for this objective, or is something missing?"

To answer that question, you need more than a preference for digital channels. You need a framework for comparing the two approaches objectively — one that holds up when the client pushes back, when the budget is mixed, and when the right answer is not obvious.

That is what this lesson provides.

---

## Core Concepts

### The Starting Point: Neither Is Better

The most important thing to understand before comparing these two models is that neither is inherently superior.

Digital marketing is not better than traditional marketing. Traditional marketing is not outdated. Each has a different structural profile — different strengths, different constraints, different optimal use cases. The professional question is never "which one is better?" It is: "which one is more appropriate for this objective, this audience, and this context?"

That question requires a framework.

---

### Traditional Marketing: What It Is and What It Does Well

**Traditional marketing** refers to activity delivered through offline channels: print (newspapers, magazines, direct mail), broadcast (television, radio), outdoor (billboards, transit advertising), and events.

Its defining characteristic is mass reach. A television commercial or a billboard reaches everyone in the broadcast area or geographic footprint — regardless of whether they have expressed any interest in the product. The audience is not selected. It is exposed.

This is a genuine strength in specific contexts. A brand launching a new product to a broad general audience needs mass awareness quickly. A public health campaign targeting an entire city cannot rely on the audience to seek out the message. A luxury brand that wants to signal credibility through the editorial environment of a respected print publication cannot replicate that context digitally.

Traditional marketing also carries a different kind of credibility. Appearing in a national newspaper or on prime-time television communicates a level of investment that audiences associate with established organizations.

Its structural limitations are equally real: fixed cost commitments, limited audience control, and — critically — a measurement model based on proxies rather than direct data.

---

### Digital Marketing: The Structural Differences

You defined digital marketing in Lesson 1. What this lesson adds is the structural contrast.

The differences that matter most in practice are:

**Reach model.** Traditional marketing distributes broadly. Digital marketing targets specifically. A paid search ad appears only to people who searched for a defined term. A social ad reaches only the audience parameters you set. That precision is operationally significant — it means budget is allocated toward people more likely to act, not toward the full broadcast population.

**Measurement.** Traditional marketing measures through proxies: estimated audience size, circulation figures, recall surveys. Digital marketing measures through direct data: impressions served, clicks recorded, conversions completed. The difference is not just granularity — it is accountability. Digital performance can be tied to the business objective that authorized the budget.

**Cost structure.** Most traditional channels operate on fixed spend: you buy the placement regardless of how it performs. Most digital channels operate on variable spend: you pay per impression, per click, or per conversion, and you can pause at any point. This gives digital programs a different risk and flexibility profile.

**Campaign adjustability.** A print ad cannot be changed after it runs. A radio spot is booked in advance. A digital campaign can be paused, adjusted, or redirected while it is running — based on performance data. This is a material operational advantage when objectives shift or when early results indicate the initial approach is underperforming.

**Speed to market.** A billboard campaign requires weeks of production and booking. A digital campaign can be live within hours. For time-sensitive objectives, this is not a minor detail.

---

### When Traditional Marketing Is the Stronger Choice

- The objective requires mass, undifferentiated awareness across a broad geographic or demographic footprint.
- The target audience has low digital engagement (some older demographics, specific geographic markets).
- Brand credibility requires the editorial authority of a high-prestige publication or broadcaster.
- The message requires a physical presence (outdoor advertising near a point of sale, for example).
- A regulatory or public service context requires guaranteed coverage of a defined population.

---

### When Digital Marketing Is the Stronger Choice

- The audience can be identified and reached with precision.
- The objective requires measurable performance against a specific business outcome.
- The budget requires flexibility, optimization, or the ability to reallocate mid-campaign.
- Speed to market is a constraint.
- The program requires ongoing iteration based on performance data.

---

### The Integrated Approach

In professional practice, the choice is rarely binary.

Most effective marketing programs use both approaches — traditional channels for broad awareness and credibility, digital channels for targeted reach, measurable conversion, and ongoing optimization. Each does what it does best. Neither attempts to do what the other does better.

The practitioner's job is not to advocate for one model. It is to understand what each contributes and recommend the combination that serves the business objective.

---

## Professional Thinking

| Dimension | Traditional Marketing | Digital Marketing |
|---|---|---|
| Reach model | Mass, broadcast | Targeted, defined audience |
| Measurement | Proxy metrics (estimated reach, recall) | Direct performance data (clicks, conversions) |
| Cost structure | Fixed spend, pre-committed | Variable spend, adjustable |
| Audience control | Limited — geographic or demographic proxy | High — behavioral, demographic, intent-based |
| Campaign adjustability | Low — committed at booking | High — adjustable while live |
| Speed to market | Weeks | Hours to days |
| Credibility signal | High — established editorial environments | Context-dependent |

A practitioner who treats this table as a reason to always choose digital has misread it. The table is a decision tool, not a verdict. Every row is a question: which profile is more appropriate for this specific objective?

---

## Inside Oravil

When Oravil reviews a client brief that includes both digital and traditional spend, the first question is not "how much of each?" It is: "what is each channel expected to do?"

If a client cannot answer that question for their traditional activity, Oravil treats the spend as unaccountable — not necessarily wrong, but impossible to evaluate or optimize.

The internal standard is that every channel in a recommended plan must have a defined role, a rationale, and a measurable or evaluable contribution to the stated objective. That applies equally to a TV spot and a paid search campaign.

When junior team members review mixed-channel briefs, they are expected to use a structured framework — not intuition, not bias toward digital, not deference to what the client has always done. The question is always the same: does this channel combination serve the objective, for this audience, in this context?

If the answer requires traditional channels, that is the recommendation. If it requires digital, that is the recommendation. If it requires both, the plan should explain exactly what each does and why the other cannot do it instead.

---

## Practical Example

A national pharmacy chain wants to promote a flu vaccination program before winter. Their objective: increase vaccination bookings by 25% versus the previous year, with bookings measured through their online appointment system.

They currently run radio ads in October and poster campaigns in-store.

A structured channel assessment produces the following:

**Radio ads** reach a broad audience and reinforce awareness. They cannot be targeted to people who are high-risk or overdue for vaccination. Performance is estimated, not measured. Role: awareness at scale.

**In-store posters** reach people who are already present at the point of decision. They cannot generate new footfall. Role: conversion prompt at point of sale.

**Digital channels** (not yet defined in detail — that is Phase 2) can reach high-risk groups by demographic parameters, drive traffic directly to the online booking system, and measure bookings as a direct outcome. Role: targeted reach and measurable conversion.

The conclusion is not that traditional channels should be removed. It is that digital channels address the measurement gap and can target segments the broadcast activity cannot reach. The recommendation is an integrated program: traditional for awareness, digital for targeted conversion — with the business objective measurable through the digital component.

---

## Hands-on Workshop

This workshop adds the second entry to your Module 1 Landscape Map.

**Task: Build a Comparison Table**

Step 1. Open your Landscape Map document from Lesson 1.

Step 2. Create a new section titled: **Digital vs. Traditional Marketing: Comparison Table**.

Step 3. Build a two-column table comparing digital and traditional marketing across at least five of the following dimensions:

- Reach model
- Measurement approach
- Cost structure
- Audience control
- Campaign adjustability
- Speed to market
- Credibility signal

Use your own words. Do not copy the table from this lesson.

Step 4. Below the table, write a recommendation of 2–3 sentences for the fitness studio client from Lesson 1. The client currently uses a magazine ad and outdoor signage. Based on your comparison, which approach — digital, traditional, or integrated — would you recommend, and on what basis?

Step 5. Save the document.

---

## Assignment

You are working with a colleague who has just come from a traditional advertising background. She argues: "Digital marketing is only effective for e-commerce. For service businesses with local audiences, traditional is always the right call."

Write a 150–200 word professional response. Your response must:

- Identify what is correct in her position.
- Identify what the position overlooks.
- Use at least three terms from this lesson accurately.
- Reach a conclusion without dismissing either model.

Write as you would in a professional team discussion — not as a debate, and not as a lecture.

---

## Deliverable

A completed comparison table in your Module 1 Landscape Map contrasting digital and traditional marketing across at least five dimensions, plus a written recommendation of 2–3 sentences for the fitness studio client identifying the appropriate channel approach and the reasoning behind it.

---

## Common Mistakes

**Treating digital as the default correct answer.**
Channel selection is a decision, not a preference. Defaulting to digital without evaluating the objective, audience, and context produces the same poor reasoning as defaulting to traditional.

**Presenting traditional marketing as obsolete.**
Traditional channels continue to perform specific functions that digital cannot replicate at equivalent cost or credibility. Dismissing them in a client conversation damages professional credibility.

**Confusing reach with effectiveness.**
A channel that reaches more people is not necessarily more effective. Effectiveness is measured against the objective. A targeted digital campaign reaching 10,000 qualified prospects may outperform a broadcast campaign reaching 500,000 unqualified ones.

**Measuring traditional and digital activity on the same metric.**
They have different measurement models by design. Attempting to force proxy metrics onto a digital campaign, or demanding direct conversion data from a billboard, misrepresents what each channel is built to do.

**Recommending integration without defining roles.**
Saying "we should use both" is not a strategy. An integrated recommendation must specify what each channel contributes and why the other cannot do it instead.

---

## Troubleshooting

**"I'm not sure which dimensions to use when comparing the two models."**
Start with the four that matter most in practice: reach model, measurement, cost structure, and audience control. Add speed to market and campaign adjustability when the client context makes them relevant.

**"The client wants to drop traditional entirely and go all-digital. Should I support that?"**
Only if the objective, audience, and context support it. If the client's audience includes segments with low digital engagement, or if the objective requires mass awareness at a scale that digital cannot match cost-effectively, dropping traditional entirely may not serve the business. The recommendation should follow the analysis.

**"How do I explain measurement differences to a client who doesn't understand digital data?"**
Use the proxy versus direct data framing. Traditional measurement estimates how many people may have seen the message. Digital measurement records how many people took a defined action. Both are valid — they answer different questions.

---

## Knowledge Check

Answer the following without reviewing the lesson.

**1.** Name three structural dimensions on which digital and traditional marketing differ. For each, describe the difference in one sentence.

**2.** A client says: "We've always done radio and it works." What question would you ask before deciding whether to recommend keeping it?

**3.** Describe one scenario in which traditional marketing is clearly the stronger choice. Explain why digital cannot replace it in that scenario.

**4.** What is the difference between a proxy metric and direct performance data? Why does it matter for accountability?

**5.** A colleague recommends an integrated campaign but cannot explain what role each channel plays. What is wrong with the recommendation?

---

## Lesson Summary

Digital and traditional marketing are structurally different models with different strengths, measurement systems, cost profiles, and appropriate use cases. Neither is categorically better.

Traditional marketing delivers mass reach, established credibility, and a physical presence that digital cannot replicate in all contexts. Digital marketing delivers targeting precision, direct performance measurement, campaign adjustability, and speed that traditional channels cannot match.

Professional practitioners compare both models across relevant dimensions — reach, measurement, cost structure, audience control, adjustability — and recommend the combination that serves the stated business objective. Recommending integration is only valid when each channel has a defined, non-redundant role in the plan.

The practitioner who can reason through this comparison objectively, without defaulting to either model, earns credibility with clients who have experience with both.

---

## Additional Resources

- Google Digital Garage — [Fundamentals of Digital Marketing](https://learndigital.withgoogle.com/digitalgarage/course/digital-marketing)
- HubSpot Academy — [Digital Marketing Certification](https://academy.hubspot.com/courses/digital-marketing)
- American Marketing Association — [Definition of Marketing](https://www.ama.org/the-definition-of-marketing-what-is-marketing/)

---

## Next Lesson

**Lesson 3 — Core Digital Marketing Channels**

You can now compare digital and traditional marketing and reason about when each applies. The next question is: what are the individual digital channels, what does each one do, and where does each one fit in a marketing program?

Lesson 3 maps the digital channel landscape — not to teach any channel in depth, but to give you a working orientation before the detailed phase-by-phase study begins.
MARKDOWN;
    }

    // Source: academy/learning-paths/digital-marketing/phase-0/module-1/lesson-03-core-digital-marketing-channels.md
    private function lesson3Content(): string
    {
        return <<<'MARKDOWN'
# Core Digital Marketing Channels

---

## Lesson Information

| Field | Value |
|---|---|
| **Learning Path** | Digital Marketing |
| **Phase** | Phase 0 — Foundations |
| **Module** | Module 1 — The Digital Marketing Landscape |
| **Lesson** | 3 of 4 |
| **Difficulty** | Beginner |
| **Estimated Duration** | 30–40 minutes |
| **Prerequisites** | Lesson 1 — What Is Digital Marketing; Lesson 2 — Digital vs. Traditional Marketing |

---

## Learning Objectives

By the end of this lesson, you will be able to:

- Name the primary digital marketing channel categories.
- Describe the primary business purpose of each channel category in one sentence.
- Distinguish between inbound and outbound channels.
- Identify which channel category is most appropriate for a given high-level business objective.
- Classify a set of real-world marketing activities by channel category.

---

## Business Context

Your manager drops a new client brief on your desk. The client is a subscription-based meal kit company. They have three goals: attract new customers, retain existing ones, and re-engage people who cancelled their subscription.

She asks: "Which digital channels would you consider for this brief?"

You know from Lesson 2 that digital marketing is a system of connected channels. But now the question is concrete: which channels, and for what?

This is a question you will face every time you start working on a client program. The answer requires more than a list of channel names. It requires a map — a working model of what each channel category does, what kind of audience it reaches, and what business purposes it is built to serve.

Without that map, every channel decision defaults to familiarity. With it, channel selection becomes a reasoned, defensible professional judgment.

---

## Core Concepts

### The Map Before the Journey

There are six major digital marketing channel categories you need to know before detailed study begins. This lesson gives you the map. Later phases give you the navigation skills.

The first organizing principle for any channel is its reach mechanism: does the audience come to you, or do you go to the audience?

**Inbound channels** attract audiences who are already searching for something — a solution, information, or a product. The audience initiates the contact.

**Outbound channels** place your message in front of an audience that has not asked for it. You initiate the contact.

This distinction matters for channel selection. An inbound channel captures existing demand. An outbound channel creates it.

---

### Search Marketing

Search marketing places a brand in front of people at the moment they are actively looking for something relevant.

It operates through two mechanisms: organic search (earning visibility through relevance and authority) and paid search (purchasing placement in search results). Both are covered in Phase 1 and Phase 2 respectively. For now, treat them as two routes to the same outcome: reaching a person in the moment of intent.

**Business purpose:** Capture existing demand. Reach people who are already looking for what the business offers.

**Best suited for:** Direct response objectives, lead generation, e-commerce, and any objective where the audience is likely to use search to find a solution.

---

### Social Media Marketing

Social media marketing distributes content and advertising through social platforms to build awareness, engagement, and community — and, in paid form, to reach precisely defined audience segments.

Like search, it operates through organic activity (publishing content to build an audience) and paid activity (purchasing reach to defined targeting parameters). The channel detail belongs to Phase 2. What matters now is the role: social media reaches people based on who they are and what they engage with, not what they are currently searching for.

**Business purpose:** Build awareness, grow audiences, drive engagement, and reach defined demographic and interest-based segments.

**Best suited for:** Brand awareness, community building, audience development, and targeted advertising to cold audiences.

---

### Email Marketing

Email marketing communicates directly with a list of people who have given permission to receive messages. It is an owned channel — the list belongs to the organization.

Because the audience has already opted in, email reaches people with a pre-established relationship. It is not built for cold outreach to unknown audiences. It is built for deepening relationships, driving repeat purchase, and managing the customer lifecycle. Phase 3 covers this in detail.

**Business purpose:** Nurture relationships, drive retention, and manage customer lifecycle communications.

**Best suited for:** Existing customers, leads who have shown interest, re-engagement of lapsed contacts, and any objective that benefits from personalized, direct communication.

---

### Display Advertising

Display advertising places visual ads — banners, images, video — across websites, apps, and digital platforms. It reaches audiences who are not actively searching and have not opted in. It interrupts rather than attracts.

Its primary value is scale and visibility. It can reach large audiences across a wide range of digital environments. It is also used for retargeting — re-reaching people who have previously visited a website or interacted with a brand.

**Business purpose:** Build broad awareness and keep a brand visible to audiences at earlier stages of the decision process.

**Best suited for:** Brand awareness campaigns, reach at scale, and retargeting audiences who have already shown interest.

---

### Content Marketing

Content marketing creates and distributes valuable content — articles, guides, videos, podcasts — to attract, engage, and retain a defined audience. It operates primarily through owned channels and supports inbound discovery through search and social.

It does not sell directly. It builds authority, trust, and organic reach over time. The investment is in content assets that continue to deliver value after publication. Phase 1 covers content strategy and production in full.

**Business purpose:** Attract organic audiences, build authority, support search visibility, and provide value that generates trust over time.

**Best suited for:** Long-term audience building, brand authority, supporting the consideration stage of the customer journey, and reducing paid acquisition costs over time.

---

### Affiliate Marketing

Affiliate marketing distributes a brand's offer through a network of third-party partners — publishers, bloggers, comparison sites, influencers — who promote the product in exchange for a commission on results delivered.

It is a performance-based model: the brand pays only when a defined outcome occurs (a sale, a lead, a sign-up). The channel leverages existing audiences built by others. It is covered within advanced phases.

**Business purpose:** Extend reach through third-party partners and acquire customers on a performance basis.

**Best suited for:** E-commerce, lead generation at scale, and programs where cost-per-acquisition control is a primary constraint.

---

## Professional Thinking

| Channel Category | Reach Mechanism | Primary Purpose | Typical Business Use |
|---|---|---|---|
| Search Marketing | Inbound — demand capture | Reach people in the moment of intent | Lead generation, e-commerce, direct response |
| Social Media Marketing | Outbound (paid) / Inbound (organic) | Build awareness and reach defined audiences | Brand building, audience development, targeted ads |
| Email Marketing | Inbound — opted-in audience | Nurture relationships and manage lifecycle | Retention, re-engagement, lifecycle campaigns |
| Display Advertising | Outbound — interruption-based | Broad awareness and retargeting | Reach at scale, brand visibility, retargeting |
| Content Marketing | Inbound — value-based attraction | Build authority and attract organic audiences | Long-term audience growth, SEO support, trust |
| Affiliate Marketing | Outbound — partner networks | Extend reach on a performance basis | E-commerce growth, cost-controlled acquisition |

Every channel category in this table is a tool. The strategy determines which tools are used and in what combination. No single channel serves every objective. No objective is best served by every channel.

---

## Inside Oravil

When a new team member joins Oravil, they are not assigned to a channel specialism immediately. Before that happens, they are expected to demonstrate a working understanding of the full digital channel landscape.

The reason is practical. A specialist in paid search who does not understand what email marketing does will give incomplete advice to a client running both. A content marketer who cannot explain the role of display advertising cannot contribute to an integrated program review.

Oravil uses the channel map as a shared vocabulary. When a brief arrives, the first internal discussion is not "how do we run the ads?" It is: "which channel categories are relevant to this objective, and what role does each play?"

That question can only be answered by someone who has a working model of the entire landscape — not just the channel they happen to work in.

This lesson builds that model. The phase-by-phase study that follows builds the execution capability. Both are required.

---

## Practical Example

A software company sells a B2B project management tool. They have two distinct objectives:

**Objective 1:** Generate 50 new enterprise trial sign-ups per month from companies that have not heard of the product.

**Objective 2:** Convert 20% of existing trial users into paid subscribers before their trial ends.

These are different objectives, and they require different channel categories.

For Objective 1, the audience does not yet know the product exists. Search marketing can capture companies actively searching for project management solutions. Display advertising can build visibility with defined company-size or industry parameters. Social media advertising can reach decision-makers in relevant roles.

For Objective 2, the audience already exists and has taken an action. Email marketing — sending targeted sequences to trial users based on their in-product behavior — is the appropriate channel. It reaches people who have opted in and are already in a relationship with the product.

The mistake would be using the same channel for both. The objectives are structurally different. They require structurally different tools.

---

## Hands-on Workshop

This workshop adds the third entry to your Module 1 Landscape Map.

**Task: Build a Channel Classification Table**

Step 1. Open your Landscape Map document.

Step 2. Create a new section titled: **Digital Marketing Channel Categories**.

Step 3. Build a table with the following columns:

| Channel Category | Reach Mechanism | Primary Business Purpose |
|---|---|---|

Step 4. Complete the table for all six channel categories covered in this lesson. Write each entry in your own words — do not copy from the lesson.

Step 5. Below the table, answer this question in 2–3 sentences: Based on the meal kit company brief from the Business Context (attract new customers, retain existing ones, re-engage cancelled subscribers), which channel categories would you consider for each objective, and why?

Step 6. Save the document.

---

## Assignment

A colleague reviews the brief for a local independent bookshop. The shop wants to increase foot traffic to their physical store and grow online book sales. Your colleague recommends running a social media content strategy for both objectives.

Write a 150–200 word professional response that:

- Acknowledges where social media marketing is a reasonable choice.
- Identifies at least one objective for which a different channel category would be more effective.
- Names the alternative channel category and explains its role in one sentence.
- Uses at least three terms from this lesson accurately.

Write as you would in a team review — direct, professional, and constructive.

---

## Deliverable

A channel classification table in the Module 1 Landscape Map listing all six channel categories, their reach mechanism, and their primary business purpose — written in the learner's own words — plus a written channel recommendation of 2–3 sentences for the meal kit company scenario from the Business Context.

---

## Common Mistakes

**Treating channel categories and platforms as the same thing.**
Instagram is a platform. Social media marketing is a channel category. Platforms change; channel categories are stable. Thinking in categories keeps the framework usable when platforms evolve.

**Defaulting to the most familiar channel.**
Familiarity is not a selection criterion. Channel selection follows objective, audience, and context. The fact that a practitioner knows how to run paid social does not make it the right channel for every brief.

**Assuming inbound channels are always more efficient than outbound.**
Inbound channels capture existing demand — but if the demand does not exist, there is nothing to capture. A new brand with no search volume has no inbound audience to attract. Outbound channels build the awareness that inbound later captures.

**Confusing the channel category with the business outcome.**
Email marketing is a channel. Retention is an outcome. A practitioner who says "we need email marketing" has identified a tool, not an objective. The objective always comes first.

**Treating the channel map as fixed.**
New channel categories emerge. The map in this lesson reflects the current landscape. Practitioners are expected to update their mental model as the industry evolves.

---

## Troubleshooting

**"I'm not sure how to decide which channel to recommend for a given objective."**
Start with the reach mechanism. Does the audience already know they need a solution? If yes, inbound channels (search, content) are the starting point. If no, outbound channels (social, display) are needed first. Then match the channel's primary purpose to the objective.

**"Several channel categories seem to overlap. How do I distinguish them?"**
Focus on what each channel requires from the audience. Search requires active intent. Display requires nothing — it interrupts. Email requires a prior relationship. Content requires the audience to find and choose it. Those differences determine when each is appropriate.

**"How do I know when to use more than one channel category?"**
Most objectives benefit from more than one channel. The question is not whether to use multiple channels — it is whether each has a defined, non-redundant role. If two channels are doing the same job, one is redundant.

---

## Knowledge Check

Answer the following without reviewing the lesson.

**1.** Name the six channel categories covered in this lesson. For each, write one sentence describing its primary business purpose.

**2.** What is the difference between an inbound channel and an outbound channel? Give one example of each.

**3.** A startup has just launched a new product that no one has heard of. They want to generate their first 100 customers. Which channel categories would you consider, and which would you rule out immediately? Explain your reasoning.

**4.** A client says: "We want to build brand loyalty with our existing customers." Which channel category is most structurally suited to that objective? Why?

**5.** A colleague proposes using content marketing to generate quick sales during a one-week promotional campaign. What is the structural problem with that recommendation?

---

## Lesson Summary

The digital marketing channel landscape comprises six primary categories: search marketing, social media marketing, email marketing, display advertising, content marketing, and affiliate marketing. Each serves a different business function and reaches audiences through different mechanisms.

Channels are organized by how they reach audiences — inbound channels attract people who are already seeking something; outbound channels place messages in front of audiences who have not yet asked. This distinction is the first filter in any channel selection decision.

No channel category is universally appropriate. Objectives, audiences, and context determine the selection. A practitioner with a working map of the channel landscape can make those decisions systematically — and explain them clearly.

This lesson provides the map. Every subsequent phase builds the execution capability, one channel category at a time.

---

## Additional Resources

- Google Digital Garage — [Fundamentals of Digital Marketing](https://learndigital.withgoogle.com/digitalgarage/course/digital-marketing)
- HubSpot Academy — [Digital Marketing Certification](https://academy.hubspot.com/courses/digital-marketing)
- IAB — [Digital Marketing Overview](https://www.iab.com)

---

## Next Lesson

**Lesson 4 — Digital Marketing Careers**

You now have a working map of the digital marketing channel landscape. The final lesson in this module examines the professional roles that operate within it — how the discipline is structured as a career, what specialisms exist, and how practitioners typically develop from generalist to specialist.
MARKDOWN;
    }

    // Source: academy/learning-paths/digital-marketing/phase-0/module-1/lesson-04-digital-marketing-careers.md
    private function lesson4Content(): string
    {
        return <<<'MARKDOWN'
# Digital Marketing Careers

---

## Lesson Information

| Field | Value |
|---|---|
| **Learning Path** | Digital Marketing |
| **Phase** | Phase 0 — Foundations |
| **Module** | Module 1 — The Digital Marketing Landscape |
| **Lesson** | 4 of 4 |
| **Difficulty** | Beginner |
| **Estimated Duration** | 30–40 minutes |
| **Prerequisites** | Lessons 1, 2, and 3 of Module 1 |

---

## Learning Objectives

By the end of this lesson, you will be able to:

- Name the major roles within a digital marketing team.
- Describe the primary responsibility of each role in one sentence.
- Distinguish between generalist and specialist roles in digital marketing.
- Explain how digital marketing roles typically collaborate on a program.
- Describe a typical career progression path from entry-level to senior practitioner.

---

## Business Context

It is your second week on the job. You have been assigned to support a campaign for a retail client. The brief lands in your inbox with five names on the distribution list: a Paid Media Specialist, an SEO Specialist, a Content Marketer, an Analytics Specialist, and your manager, the Digital Marketing Manager.

You know what digital marketing is. You know the channels. But now you need to understand the people.

Who owns the campaign brief? Who decides the channel mix? Who is responsible for the numbers at the end? When something goes wrong mid-campaign, who acts?

These are not trivial questions. A practitioner who does not understand the team's structure will ask the wrong person for help, duplicate work that has already been done, or miss a critical handoff. In a client-facing environment, those mistakes have consequences.

This lesson answers the question before the mistakes happen: who are the professionals in a digital marketing team, and what does each one own?

---

## Core Concepts

### Why Teams Exist

Digital marketing is a multi-disciplinary practice. A single practitioner can develop broad awareness across the discipline — and in small organizations, one person often handles several functions. But at scale, no single person can execute search, paid media, email, analytics, social, and content at expert level simultaneously.

Teams exist because expertise is specialized. Each role in a digital marketing function owns a defined area — a channel, a function, or a set of responsibilities. The value of the team comes not from any single role but from how those roles work together toward a shared objective.

Understanding the team is not just useful for your own career management. It is a prerequisite for professional collaboration.

---

### The Roles

**Digital Marketing Manager**

The Digital Marketing Manager owns the overall program. They translate the business objective into a marketing strategy, assign channel responsibility to specialists, coordinate execution across the team, and report results to the client or senior stakeholders.

They do not typically execute individual channels day-to-day at scale. Their job is to ensure the channels work together toward the objective and that performance is being tracked and acted on.

---

**SEO Specialist**

The SEO Specialist owns organic search performance. They are responsible for ensuring the organization's digital content is discoverable through search engines — through a combination of technical, content, and authority-building work. Phase 1 covers this discipline in full.

They work closely with the Content Marketer (who produces the content the SEO strategy requires) and the Analytics Specialist (who measures organic performance).

---

**Paid Media Specialist**

The Paid Media Specialist owns performance across paid advertising channels — search advertising, social advertising, display, and video. They are responsible for campaign structure, targeting, bidding, and ongoing optimization against defined KPIs. Phase 2 covers this in full.

They work closely with the Digital Marketing Manager (who sets the objectives and budget) and the Analytics Specialist (who tracks conversions and ROI).

---

**Social Media Manager**

The Social Media Manager owns organic social presence and community. They are responsible for publishing, community engagement, and growing the organization's social audience through content. In organizations that do not have a dedicated Paid Media Specialist, this role may also manage paid social.

They work closely with the Content Marketer (for content production) and the Digital Marketing Manager (for channel strategy).

---

**Content Marketer**

The Content Marketer owns the creation and distribution of content assets — articles, guides, videos, case studies — that serve audience acquisition, education, and retention objectives. Their work supports SEO, social, email, and the customer lifecycle simultaneously.

They work closely with the SEO Specialist (to ensure content serves organic search objectives) and the Email Marketing Specialist (to repurpose content within email programs).

---

**Email Marketing Specialist**

The Email Marketing Specialist owns direct communication with the organization's opted-in audience — existing customers, leads, and lapsed contacts. They are responsible for campaign design, list segmentation, automation sequences, and lifecycle communications. Phase 3 covers this in full.

They work closely with the Digital Marketing Manager (for lifecycle strategy) and the Analytics Specialist (for performance reporting and segmentation data).

---

**Analytics Specialist**

The Analytics Specialist owns data collection, reporting, and performance analysis across the entire digital marketing program. They define measurement frameworks, build dashboards, track KPIs, and surface insights that inform optimization decisions. Phase 4 covers this in full.

They work with every other role — because every channel produces data that requires analysis. The Analytics Specialist is the connective layer across the team.

---

### Generalists and Specialists

Early in a digital marketing career, most practitioners operate as generalists — developing working knowledge across several functions before committing to a specialism. This breadth is valuable. It produces practitioners who understand the full program, not just their own lane.

Over time, most practitioners develop deeper expertise in one or two areas — becoming the person the team depends on for a specific function. This is the specialist.

The most effective practitioners are often described as **T-shaped**: broad awareness across the discipline, with deep expertise in at least one area. That combination enables both independent execution and effective collaboration.

---

## Professional Thinking

| Role | Primary Responsibility | Works Closely With |
|---|---|---|
| Digital Marketing Manager | Overall program strategy, coordination, and reporting | All roles |
| SEO Specialist | Organic search visibility and performance | Content Marketer, Analytics Specialist |
| Paid Media Specialist | Paid advertising performance across channels | Digital Marketing Manager, Analytics Specialist |
| Social Media Manager | Organic social presence and community | Content Marketer, Digital Marketing Manager |
| Content Marketer | Content creation and distribution | SEO Specialist, Email Marketing Specialist |
| Email Marketing Specialist | Lifecycle communication and retention | Digital Marketing Manager, Analytics Specialist |
| Analytics Specialist | Measurement, reporting, and performance insight | All roles |

No role operates independently. Every specialist depends on at least one other role for inputs, outputs, or shared accountability. A team that does not collaborate effectively produces fragmented results regardless of individual skill level.

---

## Inside Oravil

On the first day at Oravil, new team members receive an orientation to the full team structure before they are introduced to their specific role responsibilities.

The reason is deliberate. Oravil works with clients who run integrated programs — paid media, content, SEO, email, and analytics running in parallel. A paid media specialist who does not understand what the SEO team is doing cannot identify overlap, cannot avoid duplicating costs, and cannot contribute to a unified program review.

The expectation at Oravil is that every practitioner can describe what every other role on the team does — and can explain how their own work connects to it.

This does not mean every practitioner executes every function. It means every practitioner understands the handoffs: who provides inputs to their work, and who depends on their outputs. That understanding is the foundation of professional collaboration.

New hires who demonstrate this understanding early are trusted with more responsibility, faster.

---

## Practical Example

A fitness apparel brand is launching a new product line. The Digital Marketing Manager translates the launch objective — 500 online sales in the first 30 days — into a channel plan and assigns responsibilities.

The **Paid Media Specialist** runs targeted ads to cold audiences to generate awareness and drive traffic to the product page.

The **SEO Specialist** ensures the product pages are optimized for relevant search terms and that the site architecture supports indexation.

The **Content Marketer** produces supporting content — a buying guide and a video — that the SEO and social teams will use.

The **Social Media Manager** builds organic momentum before and after launch through scheduled posts and community engagement.

The **Email Marketing Specialist** sends a launch announcement to the existing customer list and a follow-up sequence to people who visited the product page without purchasing.

The **Analytics Specialist** builds the reporting dashboard, tracks sales by channel, and provides the team with a mid-campaign performance summary at day 15.

The Digital Marketing Manager reviews the day-15 data, reallocates budget from display to paid search based on early conversion data, and reports progress to the client.

No single role produced the result. The result was produced by the system.

---

## Hands-on Workshop

This workshop completes your Module 1 Landscape Map.

**Task: Build a Digital Marketing Team Map**

Step 1. Open your Landscape Map document.

Step 2. Create a new section titled: **Digital Marketing Team Map**.

Step 3. Build a table with the following columns:

| Role | Primary Responsibility | Collaborates With |

Step 4. Complete the table for at least five of the seven roles covered in this lesson. Write each entry in your own words. Do not copy from the lesson.

Step 5. Below the table, answer this question in 2–3 sentences: If you were joining a digital marketing team as a junior generalist, which two specialists would you prioritize learning from first, and why?

Step 6. Save the document. Your Module 1 Landscape Map is now complete.

---

## Assignment

Your manager is preparing to hire the first three members of a new digital marketing team for a B2B software company. The company wants to: drive inbound leads through search, build a content library, and measure all activity against defined KPIs.

She asks you to write a short internal recommendation — 150–200 words — identifying which three roles she should hire first, and explaining the reasoning.

Your recommendation must:

- Name three specific roles from this lesson.
- Explain what each role will own in this context.
- Explain why these three roles are the right starting combination for the stated objectives.
- Use at least three terms from this lesson accurately.

Write as you would in a professional team context — direct and reasoned.

---

## Deliverable

A Digital Marketing Team Map added to the Module 1 Landscape Map, listing at least five major digital marketing roles, the primary responsibility of each, and at least two clearly described collaboration relationships between roles — written in the learner's own words.

---

## Common Mistakes

**Assuming one person can own all roles.**
In small organizations, one practitioner may cover multiple functions. But conflating the roles leads to unclear ownership, missed responsibilities, and work that falls between the cracks. Even when one person does multiple jobs, the role responsibilities remain distinct.

**Treating the Digital Marketing Manager as a channel specialist.**
The manager's job is strategy, coordination, and accountability — not day-to-day channel execution. Expecting the manager to also run the ads, write the content, and build the dashboards is a structural misunderstanding of the role.

**Underestimating the Analytics Specialist.**
Analytics is not a reporting function that happens at the end of a campaign. It is an active function that informs decisions throughout. Teams that involve analytics late produce reports. Teams that involve analytics early produce better campaigns.

**Assuming specialists do not need to understand adjacent roles.**
A Paid Media Specialist who does not understand what the SEO Specialist does will run paid campaigns that compete with, rather than complement, organic strategy. Specialization does not remove the need for cross-functional awareness.

**Confusing the Social Media Manager with the Content Marketer.**
Content production and social distribution are related but distinct functions. The Content Marketer creates the asset. The Social Media Manager determines how and when it is distributed through social channels. In small teams the same person may do both — but the responsibilities are separate.

---

## Troubleshooting

**"I'm not sure which role owns a specific task."**
Ask: which channel or function does this task belong to? Then trace it to the role that owns that channel. If the task spans multiple channels, it likely belongs to the Digital Marketing Manager to coordinate, with execution delegated to the relevant specialists.

**"Several roles seem to overlap. How do I draw the line?"**
Ownership, not output, is the distinguishing factor. Two roles may both produce content — but one owns the strategy and one owns the production. The question is not what the output is, but who is accountable for the decision and the result.

**"I don't know which specialism to pursue."**
This lesson is not the place to make that decision. Specialization emerges from exposure. The learning path is designed to give you working knowledge of each discipline before you commit to a direction. Complete the phases before deciding.

---

## Knowledge Check

Answer the following without reviewing the lesson.

**1.** Name five of the seven roles covered in this lesson. For each, write one sentence describing their primary responsibility.

**2.** A new campaign requires paid search, organic content, and post-campaign reporting. Which three roles are directly involved? What does each contribute?

**3.** What is the difference between a generalist and a specialist in digital marketing? At which career stage is each most common?

**4.** A colleague says the Analytics Specialist should only be involved at the end of a campaign to produce the final report. What is wrong with this approach?

**5.** What does it mean to be a T-shaped marketer, and why is that profile valued in digital marketing teams?

---

## Lesson Summary

A digital marketing team comprises complementary roles that each own a defined area of the discipline: the Digital Marketing Manager owns the overall program; SEO, Paid Media, Social Media, Content, Email, and Analytics Specialists own their respective functions.

No role operates in isolation. Every specialist depends on inputs from others and produces outputs that others depend on. Collaboration between roles is what produces integrated results.

Career development in digital marketing typically begins with generalist breadth and moves toward specialist depth. The T-shaped profile — broad awareness with deep expertise in one area — is the most effective long-term posture.

With this lesson, Module 1 is complete. You now have a working model of what digital marketing is, how it differs from traditional marketing, what channel categories exist, and who practices the discipline professionally.

Module 2 builds the next layer: the owned, earned, and paid media framework in depth.

---

## Additional Resources

- American Marketing Association — [Marketing Careers](https://www.ama.org/marketing-news/marketing-careers/)
- Google Digital Garage — [Fundamentals of Digital Marketing](https://learndigital.withgoogle.com/digitalgarage/course/digital-marketing)
- HubSpot Academy — [Digital Marketing Certification](https://academy.hubspot.com/courses/digital-marketing)

---

## Next Module

**Module 2 — Owned, Earned, and Paid Media**

Module 1 gave you the landscape: what digital marketing is, how it compares to traditional marketing, what channels exist, and who practices the discipline.

Module 2 goes deeper into the structural model that organizes all of it. Owned, earned, and paid media are not just three categories — they are the three levers every digital marketing strategy pulls. Understanding how they interact, when to prioritize each, and how to plan across all three is the foundation of every integrated marketing decision you will make.
MARKDOWN;
    }
}
