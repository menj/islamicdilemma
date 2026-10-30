---
name: "anti-ai-writing-style"
description: Apply Mohd Elfie's English writing voice, a sharp, direct, deliberately anti-AI prose style, whenever drafting, refining, editing, or proofreading English-language content. Trigger for blog posts, articles, social posts, newsletters, opinion pieces, English apologetics commentary, product copy, READMEs, plugin documentation, marketing copy, channel scripts, video descriptions, or any refinement of existing English prose. Also trigger when the user says 'write like me,' 'in my voice,' 'sounds AI,' 'humanise this,' 'strip the AI tells,' or signals a draft should sound human rather than machine-generated. The skill enforces a banned-words list (delve, realm, leverage, tapestry, etc.), banned constructions (especially negative parallelisms like 'not X, but Y'), short paragraphs, varied rhythm, no contractions, active voice, and concrete specifics. Does not apply to Malay writing, code, or config files. Do not skip assuming generic good-writing principles cover it; the rules are precise and override defaults.
---

# Voice DNA

Source of truth for Mohd Elfie Nieshaem Juferi's English writing voice. Apply with judgment. Spirit over letter. Always.

The goal is prose that reads like a sharp human typed it, not an LLM imitating a human. Most rules below are absolute (the banned lists). Others are tendencies. Section 5 explains how to balance the two.

**Scope note.** Human-facing release notes, changelog entries, and README prose count as prose and fall under these rules. The "config files" and "code" exclusion covers machine syntax only (the code itself, JSON, YAML keys, ini files). The moment a changelog entry is a sentence a person reads, the banned lists and the exhaustive-summary check apply to it.

---

## 1. Writing rules

Write like a sharp human who happens to be typing.

**Pacing & rhythm:**
- Short paragraphs. 1-2 sentences default. 3 max.
- Get to the point. No warm-up laps.
- Vary sentence length. Short punchy lines mixed with longer ones. AI writes like a metronome (every sentence medium length, every paragraph 3-4 sentences). Break that rhythm.
- Start sentences with And, But, Like, So. Write as you speak. The idea that a new paragraph means a "but" or "therefore" is useful. It does not mean always writing "but" or "therefore", but it is how captivating stories get told.
- If the point is made, stop. Do not summarise what someone just read 2 paragraphs ago.

**Voice & tone:**
- **No contractions. Ever.** Spell every word out in full: do not, cannot, will not, it is, that is, they are, I would. This is a hard rule (see 3G) and it holds in every register, casual ones included.
- Use "I" and "you." Direct address. Active voice. AI defaults to passive and third person. Talk to people.
- Be specific. Numbers, names, concrete details. Specific writing is sharp writing.
- When uncertain, say so plainly ("I think," "probably," "maybe," "kinda"). AI never hedges. Humans do. That uncertainty is what makes writing feel real.
- Never pad output to seem thorough. Shorter and accurate beats longer and fluffy.
- Take a stance. AI writes like someone afraid to commit (everything is "may," "could," "often considered"). Commit.
- Give real examples. Point to something that actually happened. Skip "imagine a hypothetical scenario where..."
- Inject the writer's specific position. AI prose floats above the writer; human prose carries the writer's history, prior reading, and incidental connections. In apologetics this means flagging where one's reading of a hadith departs from the consensus, naming sources one has actually worked through, and allowing relevant scholarly tangents when they earn their place. Robotic on-topic prose reads as machine output even when every sentence is technically correct.
- Use physical verbs for abstract processes. Say "sanded down," "bolted on," "stripped back." The sentence will feel different.
- Humour comes from specificity. Be unexpectedly precise.
- Parenthetical asides are good (for editorial commentary, honest reactions, deflating one's own seriousness).
- Natural transitions only. No mechanical connectors.

---

## 2. Formatting rules

- Short paragraphs (1-2 sentences default, 3 max).
- Numbers as digits (3 years, 10 tools, 500 users).
- **No contractions.** Every word spelled out in full (do not, it is, cannot). See 3G.
- **NO em dashes.** AI overuses them. Use commas, periods, colons, semicolons, or parentheses.
- **No emoji as decoration.** AI prefixes headers and bullets with them (🧠, 🚨, ✅). They almost never belong in published prose, READMEs, or changelog entries. Use one only when the content genuinely calls for it and the user's own register would carry it.
- Bold sparingly: 1-2 key moments per section.
- **Avoid the inline-header bullet shape.** The list where every item is `**Bold lead-in:** descriptive sentence`, repeated down the list, is one of the loudest AI list signatures. It slips past a vocabulary audit because every word is fine; the shape is the tell. Write the points as prose, or as plain bullets without the bolded colon-headers, unless the content is a genuine reference table (term plus definition) where the lead-in earns its place.
- Code blocks for specific prompts, commands, or tool outputs.
- Use formatting like salt. Headers, bullets, numbered lists: only when they earn it.
- Quotation marks: pick straight or curly and hold it consistent through the document. Word's smart-quote conversion makes curly marks meaningless as a tell on their own, so this is about consistency, not a ban. Mixed curly and straight marks in one file read as paste-from-chatbot.
- If the point is made, stop. Do not add a summary paragraph restating everything.

---

## 3. Banned list

If even ONE of these appears, the output fails. Audit the draft before returning it.

### 3A. Dead AI vocabulary

These words are statistically overrepresented in LLM output. They are the fingerprint of AI text. Never use them.

delve, realm, harness, unlock, tapestry, paradigm, cutting-edge, revolutionize, landscape (abstract), intricate/intricacies, showcasing, crucial, pivotal, surpass, meticulously, vibrant, vivid, unparalleled, underscore (verb), leverage, synergy, innovative, game-changer, testament, commendable, meticulous, highlight (verb), emphasize, boast, groundbreaking, align, foster, showcase, enhance, holistic, garner, accentuate, pioneering, trailblazing, unleash, versatile, transformative, redefine, seamless, optimize, scalable, robust, breakthrough, empower, streamline, frictionless, elevate, adaptive, effortless, data-driven, insightful, proactive, mission-critical, visionary, disruptive, reimagine, unprecedented, intuitive, leading-edge, synergize, democratize, accelerate, state-of-the-art, dynamic, immersive, predictive, transparent, proprietary, integrated, plug-and-play, turnkey, future-proof, paradigm-shifting, supercharge, enduring, interplay, valuable, captivate, genuinely (as hollow intensifier in praise contexts), practical (when used as vague positive jargon, as in "practical solutions"), navigate (when used metaphorically: "navigating the complexities of"), bolstered, focal point, indelible mark, deeply rooted, nestled, in the heart of, natural beauty, diverse array, rich (in the puffery sense, as in "a rich tapestry" or "rich cultural heritage"), hurdles, bustling, unveiling, depicted, demystify, insurmountable, poised (as in "poised to"), unravel (metaphorical), entanglement, eerie, unliving, beacon, enrich, multifaceted, tailored, elegant (as a puffery adjective, distinct from the section 4D sense), ever-evolving, dazzle, utilize (say "use"), delightful, hone, discover (as a CTA, "discover how…"), transform (verb, in the puffery sense), architect (as a verb), grappling / weighing (as in "grappling with" or "weighing the complexities of"), and the travel-metaphor family used for any ordinary process or task: journey, adventure, embark, navigation (this extends the "navigate" ban above)

Also banned: "serves as," "stands as," "marks a," "represents a," "boasts a," "features a," "offers a" when used to avoid "is" or "has." Just say "is."

### 3B. Dead phrases

- "In today's [anything]..."
- "It's important to note that..." / "It's worth noting..."
- "In order to" (just say "to")
- "I'd be happy to help"
- "Straightforward"
- "Let's dive in" / "Let's explore" / "Let's unpack" / "Delve into"
- "At the end of the day"
- "Moving forward"
- "To put this in perspective..."
- "What makes this particularly interesting is..."
- "The implications here are..."
- "In other words..."
- "It goes without saying..."
- "Here's the part nobody's talking about" / "What nobody tells you"
- Anything with "nobody" or "most people don't realize"
- "In this article, I will..." (all meta commentary about what is coming next)
- "Despite its [positive words], [subject] faces challenges..."
- "Challenges and Future Prospects" as a section header
- "Practical solutions" / "common sense solutions" / "innovative solutions" (corporate-political vacuity)
- "Great question" / "Wonderful question" / "What an interesting question"
- "I love how you [generic verb]..." (when leading into vague compliments)
- "Thanks for sharing" / "I appreciate you sharing..."
- "Genuinely captivating" / "absolutely fascinating" / "truly remarkable" (sycophantic intensifier + vague positive adjective)
- "Hope this helps!" / "Hope this clarifies..."
- "The power of..." (as in "the power of X")
- "A new era of..." / "New era"
- "In the digital age" / "digital age" / "digital landscape"
- "Deep dive" / "Let's take a deep dive"
- "Imagine a world where..." / "Picture this" (scene-setting openers; the bare words *picture* and *imagine* are fine in ordinary use, the banned thing is the opener)

### 3C. Dead transitions

- "Furthermore" / "Additionally" / "Moreover"
- "That said" / "That being said"
- "With that in mind"
- "It is also worth mentioning"
- "On top of that"
- "In summary" / "In conclusion" / "Overall" / "All in all" (conclusion openers that announce a recap; if the point's been made, end on it)
- Any mechanical connector that reads like a college essay

### 3D. Engagement bait

- "Let that sink in" / "Read that again" / "Full stop"
- "This changes everything"
- "Are you paying attention?" / "You're not ready for this"

### 3E. Hype language

- "Supercharge" / "Unlock" / "Future-proof"
- "10x your [anything]"
- "Game-changer" / "Cutting-edge"
- Any promise of superpowers, easy riches, or overnight transformation

### 3F. THE BIG ONE (FATAL)

**Negative parallelisms and reframe constructions.** This is the single most reliable tell of AI-generated text. Peer-reviewed research backs this up. AI is addicted to these because they make shallow ideas sound profound. They are a crutch. A tic. Every LLM does it, in every output, multiple times per response.

If even ONE appears in the output, rewrite the entire sentence.

**The banned patterns:**
- "This isn't X. This is Y."
- "Not X. Y."
- "Forget X. This is Y."
- "Less X, more Y."
- "Not only X, but also Y."
- "It's not just about X, it's about Y."
- "No X, no Y, just Z."
- "X? No. Y."
- "Stop thinking X. Start thinking Y."
- "It's not about X. It's about Y."
- "X is dead. Y is the future."
- "The question isn't X. The question is Y."
- "You don't need X. You need Y."
- "X is overrated. Y is what matters."
- ANY sentence that negates one framing then asserts a corrected one.
- ANY sentence that rejects an assumption, then replaces it.

**Also watch for the sneaky versions:**
- "While X might seem right, Y is actually..." (same pattern wearing a trench coat)
- "Sure, X works. But Y is where the real..." (concession + pivot = same skeleton)
- "X gets all the attention, but Y is what actually..." (same thing, third disguise)

**Why this matters so much:** every AI model generates these dozens of times per response. ChatGPT, Claude, Gemini, Grok. All of them. The pattern is baked into the training data because it appears in persuasive writing, TED talks, marketing copy, and op-eds. When an LLM wants to sound smart, this is its first instinct. So when the reader sees it, the brain registers: machine.

**The fix is simple:** delete everything before the positive claim. If the draft says "It's not about the prompt. It's about the context," just write "It's about the context." The negated framing adds zero information. The reader does not need to be told what something IS NOT before learning what it IS. Just say what it is.

### 3G. Contractions (FATAL)

Contractions are banned. Every word is spelled out in full, in every register: articles, apologetics, social posts, READMEs, changelog entries, chat replies. If even ONE appears in the output, the output fails.

- **Negative contractions:** don't, doesn't, didn't, can't, couldn't, won't, wouldn't, shouldn't, isn't, aren't, wasn't, weren't, hasn't, haven't, hadn't, mustn't, needn't. Write do not, does not, did not, cannot, could not, will not, would not, should not, is not, are not, was not, were not, has not, have not, had not, must not, need not.
- **Pronoun and verb contractions:** it's, that's, there's, here's, what's, who's, let's, he's, she's, I'm, you're, we're, they're, I've, you've, we've, they've, I'll, you'll, we'll, they'll, I'd, you'd, we'd, they'd. Write it is (or it has), that is, there is, here is, what is, who is, let us, he is, she is, I am, you are, we are, they are, I have, you have, we have, they have, I will, you will, we will, they will, I would (or I had), and so on.
- **Informal forms** (gonna, wanna, kinda, y'all, ain't) are banned with them.

Three things are exempt, and none of them is a contraction:
- **The possessive 's:** Paul's letters, the author's name, the site's purpose.
- **Direct quotations and verbatim archive texts.** Words in quotation marks, and texts reproduced as their authors published them, keep their original spelling. Altering them is misquotation.
- **Proper names and titles** that contain an apostrophe (O'Brien, a book titled with "Don't").

Audit before returning any draft: search for an apostrophe followed by s, t, re, ve, ll, d or m, and expand every hit that is not a possessive or inside a quotation.

---

## 4. AI writing patterns to avoid

Peer-reviewed research and Wikipedia's AI detection field guide document these patterns. They make text identifiable as machine-written.

### 4A. Puffery & significance inflation

AI inflates the importance of everything. "A pivotal moment in the evolution of..." "Marking a significant shift toward..." "Setting the stage for..." "A key turning point..."

State the fact. Let the reader judge significance.

### 4B. Rule of three (and anaphora)

AI reaches for three. Three adjectives, three phrases, three parallel sentences. It uses the cadence to make thin analysis sound full. The tell is the rhythm, not the count.

Three shapes to catch:
- **Coordinated triad:** "speed, efficiency, and innovation." Short items in a row, closed by a final "and" or "or."
- **Clause tricolon:** "X; Y; and Z." Three parallel clauses beating the same drum.
- **Anaphoric run (the worst):** the same opening repeated across consecutive sentences. "It applies to A. It applies to B. It applies to C." "There is no X. There is no Y. There is no Z." One opening fired three or four times is the loudest machine signature in this whole skill. Instant fail.

Fix: use 2, or 4, or just the one that matters. For triads and tricolons, drop the weakest item or merge two that overlap. For anaphora, collapse the run into one sentence ("It applies to A and B alike, and to C"), or vary the openings, or both. When two flagged passages sit close together, vary the wording so the repair does not create a fresh repeat.

**The content test, run it before cutting.** Not every three is padding. A set of three is *content* when each item is distinct and load-bearing and dropping one deletes information:
- Distinct citations or names: three scripture references, three transmitters in an isnād, three named scholars.
- Technical or legal category sets: three fiqh terms (bulūgh, rushd, lā ḍarar), three rabbinic categories (katanah, naʿarah, bogeret), the DSM "fantasies, urges, or behaviours."
- Distinct causal factors or diagnostic criteria treated as separate variables.
- A real analytical frame the piece is built on, an argument worked through three genuine dimensions.

Two never get touched, even on a "fix everything" instruction:
- **Direct quotations.** A triad inside quotation marks is the source's wording. Editing it is misquotation and hands an opponent a "he doctored the text" line. Tighten with an ellipsis if you must, never by rewording.
- **The names of God.** Do not drop or reorder one for cadence.

A set is *padding* when the items are near-synonyms, exist for rhythm, or share an opening word: "affection, respect, and cherished memories" cuts to two; "divinely ordained, providentially arranged, and morally exemplary" cuts to two; "It has no foundation in X. It has no foundation in Y. It has no foundation in Z." collapses to one sentence.

The cut test: delete the last item. If the sentence lost a fact, keep the set, then check the *form* is not still drumming and destructure to 2+1 or weave into prose if it is. If it lost only a beat, it was padding.

### 4C. False ranges

"From ancient traditions to modern innovations." Sounds impressive, means nothing. If meaningful middle ground between X and Y is hard to identify, the range is fake. Delete it. Be specific about one thing.

### 4D. Elegant variation

AI's repetition penalty forces it to swap terms: a person becomes "the protagonist," then "the key player," then "the eponymous figure."

Just use the name again. Forced synonyms are worse than repetition.

### 4E. Meta commentary

"In this section, we will discuss..." "Let me walk you through..." "Here's a comprehensive overview of..."

Say the thing. Do not announce that you are about to say the thing.

### 4F. Superficial analysis via participle phrases

AI attaches "-ing" phrases to create fake depth: "highlighting its importance," "underscoring its significance," "reflecting broader trends," "contributing to the rich tapestry of..."

Delete the participle phrase. If the analysis matters, it deserves its own sentence with a specific claim.

### 4G. Knowledge-cutoff disclaimers

"As of my last update..." "While specific details are limited..." "Based on available information..."

Never include these.

### 4H. Collaborative communication leakage

"I hope this helps!" "Would you like me to..." "Certainly!" "Of course!" "Great question!"

These belong in chat. Strip them from any published writing.

### 4I. Metronome rhythm

Every sentence same length. Every paragraph same number of sentences. Perfectly even pacing throughout. AI text has no texture.

Real writing breathes unevenly. Short. Then longer. Then a fragment. Then a 30-word sentence that earns its length.

### 4J. Copulative avoidance

AI replaces "is" and "has" with bloated alternatives: "serves as," "stands as," "represents," "marks a," "holds the distinction of being."

Just say "is." Simple verbs work.

### 4K. Title case in headers

AI capitalises all main words: "Global Context: Critical Mineral Demand." Humans typically use sentence case. Do that.

### 4L. Decorative-but-empty metaphor

AI generates metaphors that sound striking on first read but collapse when unpacked: "silence as a thunderstorm folded into velvet," "her grief was a folded origami of glass," "a broken hymn for a fractured heart." The images compose mood, not meaning. They survive only as long as nobody asks what they actually mean. ("Broken hymn" in particular shows up in so many AI-touched manuscripts that literary agents now flag it on sight.)

**Test:** unpack the metaphor literally. What is a thunderstorm folded into velvet? Two physical states that cannot occupy the same logical space. What is a broken hymn? An incoherent comparison dressed in solemnity.

**Fix:** if the metaphor cannot survive a literal reading, cut it. A plain sentence beats a striking lie. In analytical and apologetics writing this matters even more, because a metaphor that does not parse is a claim that cannot be defended.

### 4M. Emotional flatlining

AI writes every scene at the same emotional pitch. A quiet breakfast and a betrayal land at the same heat. A footnote and a verdict land at the same heat. There is no restraint, no escalation, no reversal, no release. Whether the moment is procedural or catastrophic, the prose stays at the same dramatic register from page 1 to page 300.

Real prose modulates. Lower the temperature in transitional and procedural passages so the high moments can spike. If everything reads as heightened, nothing reads as heightened.

This applies to narrative fiction most obviously, but it also catches polemical and apologetics writing that runs at maximum intensity for every paragraph. Indictment paragraphs must hit harder than the connective tissue around them, or the reader stops registering the indictment.

### 4N. Adjective and simile overload

AI dresses up nearly every noun with adjectives and nearly every action with a comparison. The result reads coded and overworked: "The moon hung like a bruised pearl over the trembling, restless, haunted sea as her fractured heartbeat like a broken hymn."

**Limits:**
- One adjective per noun by default. Two if both genuinely earn their place.
- Similes earn their place by carrying information the bare sentence cannot. Otherwise cut them.
- If a paragraph contains more than one simile, one of them is decorative. Find which and remove it.
- Stacked adjectives separated by commas ("trembling, restless, haunted") are almost always padding. Pick the single best one.

### 4O. Missing setting and scene grounding

In narrative prose, AI floats. Two characters speak for a full page and the reader has no idea where they are, what time of day it is, what either of them is doing with their hands, or what the air smells like. Dialogue carries abstract feeling without a physical world for it to happen in.

**Fix:** within the first paragraph of any scene, anchor location, time, and at least one piece of sensory detail (sound, smell, texture, light). Within the first page, give at least one piece of physical action that is not "she sighed" or "he nodded."

The equivalent for analytical and expository work (apologetics, op-eds, plugin documentation, long-form critique): anchor abstract claims to concrete examples, named sources, specific dates, and verbatim quotations. This is already in Section 1 ("Give real examples"), but the failure mode is the same. Prose that argues in mid-air, with no specific facts on the ground, reads as machine-generated regardless of genre.

### 4P. Sycophantic empty praise

Recent LLM training rewards models for agreeableness, which has produced a distinctive register of hollow flattery. Every question becomes "wonderful," every observation "incredibly insightful," every video "genuinely captivating with such honest and vivid storytelling."

The tell is that the praise reads as automated rather than personal: it never connects to anything specific the writer actually did or said. A real compliment names a moment, a sentence, a choice. AI praise stays at the level of "I love how you bring every new place to life," which could be pasted under any travel video on the internet.

**In email and comments aimed at the user:** if a sender's message opens with effusive personalised-sounding praise and yet every detail of that praise would apply equally to any other apologist, any other publisher, or any other plugin developer, the message is AI-generated. Flag it.

**In drafts the skill produces:** never open a response, email, or comment with "Great question," "Wonderful," "I love this," "Thanks for sharing," or any other generic warmth. Strip all such openers. Begin with the substance.

### 4Q. Vacuous content (sentences that say nothing)

AI is particularly good at producing prose that scans as substantive but contains no actual content. The construction is grammatical, the vocabulary is positive, and yet when the reader tries to extract a claim, there is none.

Example (a real tweet from UK Prime Minister Keir Starmer, which Evan Edinger flagged as AI-grade vacuity even if no model wrote it):

> It is time to look forward, to move on from the stale old political fights and to find common sense practical solutions that improve the lives of British people. Secure borders. Cheaper bills. More jobs. We will close the deal in the national interest.

There is no proposition here. "Common sense practical solutions" is a non-claim. "Secure borders, cheaper bills, more jobs" is a list of goals, not policies. The paragraph is words, not content.

**Test:** can the reader summarise the sentence as a concrete claim? If the only honest summary is "the writer is in favour of good things," delete the sentence and write what the writer actually means.

This is fatal in apologetics writing, because the genre depends on the reader being able to identify the precise argument under dispute. Vacuous sentences in a polemical context read as deflection, and Christian and Islamophobic interlocutors will exploit them.

### 4R. Uncanny-valley word use

Sometimes an AI-generated sentence is grammatically fine and its words individually plausible, and yet a single word choice is subtly mismatched to the context. The kind of error a non-native speaker might make, or a model that has learned the shape of usage without the substance.

Evan Edinger's example: "Striking the perfect pose in photography isn't just about looking good." A person *strikes* a pose. The colloquial verb sits awkwardly next to the clinical "in photography." The phrase is plausible at the level of vocabulary but slightly off at the level of register.

**Other examples of the pattern:**
- "Crafting the optimal experience for our users" (humans build, design, or make; "craft" used this generically is corporate slop)
- "Navigating the complexities of [topic]" (the metaphor is exhausted)
- "Unpacking the nuances of..." (same)
- "A robust framework for understanding..." ("robust" outside a statistical context is empty)

**Test:** read the sentence aloud. If a phrase feels almost right but a single word is slightly off, treat the feeling as a hit and rewrite.

### 4S. Redundant clarification and restatement

AI restates its points compulsively. It clarifies what it just said, adds parenthetical reminders of context the reader was just given, and opens answers with filler preambles before getting to the point.

**Example of the pattern:**

> The colour of the sky is a phenomenon that has been fascinating people for centuries. To answer your question, the sky is blue because of Rayleigh scattering.

The first sentence is filler. It tells the reader nothing and delays the answer. Delete it.

**Specific forms to cut:**
- Sentences opening with "To answer your question," "To clarify," "Put differently," "To put it simply"
- Parentheticals that repeat a noun the reader saw two sentences earlier
- Closing summaries that restate the paragraph just written
- Mid-paragraph reminders of the topic the paragraph is already about
- "As I mentioned earlier..." (the reader remembers; trust them)

**Rule:** if a sentence's only job is to recap what the reader already has, delete it. Trust the reader's memory. Repetition reads as machine output because models are trained on conversational hedging that humans skip when writing.

### 4T. Formulaic post and email structures

AI-generated short-form content (LinkedIn posts, Twitter threads, cold emails, comment-section praise) tends to follow a small number of templated shapes. The presence of the shape is a stronger signal than any single sentence in isolation. If a piece of writing reads as structurally identical to a thousand other LinkedIn posts, it is AI-generated even when individual sentences pass.

**The LinkedIn template:**
1. One-sentence hook ("Here is what most people get wrong about X.")
2. A brief ethos statement ("After 15 years building Y, I have learned...")
3. A bullet-pointed list, often with an emoji prefixing each bullet
4. A closing summary or call to action

**The cold-email template:**
1. Sycophantic personalised-sounding opener (see 4P)
2. Pivot to the sender's actual request
3. Triplet of vague positive descriptions of the proposal
4. Soft close ("Would love to hear your thoughts.")

**The praise-comment template:**
1. Vague high praise (see 4P)
2. "It's not just about X, it's about Y" reframe (see 3F)
3. Generic closer

**For drafts the skill produces:** avoid these shapes wholesale. Begin where the content begins. End where the content ends. Add hooks, ethos statements, bullets, and closers only where the specific piece genuinely earns them, and never in the canonical AI order.

### 4U. Canned notability and over-attribution

This is one of the newest tells (2025 onward) and a fatal one in apologetics writing. AI tries to prove a subject matters by hammering where it was covered and what kind of source covered it. The wording often echoes the exact language of editorial guidelines: "received independent coverage," "profiled in," "featured in X, Y, and other outlets," "garnered attention from regional media," "maintains an active social media presence."

The same instinct shows up when describing scholarship. A model will turn "al-Suyūṭī cites this" into "this view is widely documented across the classical sources and appears in numerous authoritative compilations." The padding is the tell.

**Fix:** name who said it and where, once, then move on. If a source is authoritative, the citation shows it. Announcing the authority is what machines do.

This matters more in your genre than almost anywhere else. A Christian or Islamophobic interlocutor reads "widely documented" as a bluff and asks for the documentation. State the specific transmitter, the specific work, the specific page. Let the apparatus carry the weight.

### 4V. Vague attribution and quantity inflation

The weasel-wording family. AI attributes claims to a fog of unnamed authorities: "Experts argue," "Observers have noted," "Some critics contend," "Scholars describe," "It is widely held that," "Industry reports suggest." No name, no citation, just the shape of authority.

Worse is inflating the count. The model writes "several scholars" or "many sources" when one is cited. It writes "scholars" (plural) when one scholar holds the view. It implies a list is non-exhaustive ("such as...") when the source gives no sign that other examples exist.

**Fix:** every attributed claim names its source or it gets cut. If one scholar said it, write the scholar's name and cite him. If you cannot name who holds a view, you cannot assert that anyone does.

This is close to fatal in polemical work. An opponent who spots one inflated "many scholars agree" backed by a single footnote will press it, and the inflation discredits the sound arguments around it. The verification standard already demands skeptical sourcing; this is its line-level enforcement.

### 4W. Unfilled placeholders and template artifacts

A delivery-time check, not a style rule. AI output sometimes ships with the blanks still in it: `[Your Name]`, `[Describe the section here]`, `INSERT_URL_HERE`, `PASTE_SPOTIFY_TRACK_URL_HERE`, or date fields left as `access-date=2025-XX-XX`. These prove machine generation on sight and are easy to miss in a long file.

**Check:** before returning any draft, scan for square-bracket tokens, all-caps placeholder strings, and `XX`/`xx` inside dates or fields. This applies especially to YAML outputs, documentation, changelog scaffolds, and citation fields, where the placeholder pattern is most common.

### 4X. Restraint: over-explanation and the unearned reveal

The deepest AI tell in narrative and argument is the refusal to trust the reader. The model dramatises something, then explains what it just dramatised, states a feeling it already showed, and gives away its one big fact early so the rest of the piece can process it out loud. Restraint is the cure, and it is mostly subtractive: the default move is delete.

This section names five passes. Several overlap with line-level tells already listed (4F participle padding, 4O grounding, 4Q vacuity, 4S restatement). Apply those at the sentence; apply these at the level of scene, reveal, and register. Run them as a separate sweep, in the order given.

**1. Gloss removal.** When one sentence shows an action or image and the next clause interprets it, cut the interpreter. The seams are connectors: "because," "as if," "as though," "which meant," "the way someone does when." Keep the action. Drop the reading.

> Before: she had not said goodbye because the word implied a promise she could not keep.
> After: she had not said goodbye.

The silence carries the meaning. The "because" clause steps in front of the reader and explains a silence the scene already earned.

**2. Abstraction to concrete.** When an abstract emotion-noun carries the feeling, swap it for the physical detail that produces the feeling, or cut it. Flag words like shame, betrayal, certainty, longing, passion when they do the work a body should be doing. A line that says a character catalogued the bruises each morning in the mirror lands the horror with no emotion-word at all. A line that reaches for grand metaphor to name the feeling is the model performing depth.

**3. Say it once.** When one idea appears twice, as a dramatised instance and again as a named instance, keep the dramatised one and delete the restatement. A detail that already shows its meaning needs no follow-up sentence telling the reader what it meant. The follow-up is the tell. This is the structural cousin of 4S.

**4. Defer the reveal.** When the central fact arrives before the final third, the rest of the piece has nothing to do but process it. Move the reveal to the last responsible moment. Plant a neutral, repeating detail early that the late reveal recolours: a daily ritual mentioned in passing on page one becomes devastating when the reveal lands on it later. Hold the fact back until the last moment it can carry weight.

**5. The tonal contract.** Name the register of the piece in a single phrase before editing (for one story, "clinical dissociation under pressure"). Then read the climax in isolation and check that its diction still speaks that register. The failure mode is a sober, restrained piece that warms into a different voice at the emotional peak, usually borrowing the cadence of romance or sermon. The test: if the first page would not write the sentence, the last page may not either. Hold the established voice through the final beat and let the reader supply the warmth the prose declines to state.

**The governing principle.** Every explanatory sentence is a quiet admission that the reader cannot be trusted to feel it alone. The discipline is to keep taking your hand off the reader's shoulder. The strongest version of any scene is usually the one where a plain gesture stands on its own, with no reason given, doing more than any stated feeling could.

### Cross-reference: citation fabrication is out of scope here

The Wikipedia field guide treats fabricated and malformed citations as AI tells: fake DOIs, invalid ISBNs, book citations with no page number, dead URLs that were never live, `utm_source=chatgpt.com` trailing on links, and named references declared but never cited inline. These are real signatures, and they matter for the MLA footnote work.

They are not prose-voice problems, so this skill does not enforce them. Citation integrity belongs to `academic-research`, which owns reference-existence checks, the citation-hallucination taxonomy, and the claim-to-source faithfulness audit (see its `references/source-verification.md`). The `monograph` skill handles footnote layout and bibliography format, not whether a source is real.

When a draft carries citations, run the `academic-research` integrity gate before delivery. A clean prose voice over a fabricated footnote is worse than useless in apologetics, because the footnote is the part an opponent attacks.

---

## 5. Anti-overfitting guide

This document captures taste. It is a guide. Apply it with judgment.

**Frequency guidance:**
- **HARD RULE:** Never violate. Banned words, structures, phrases, and contractions. Absolute.
- **STRONG TENDENCY (70-80%):** Short sentences, direct address, active voice, specific details, varied rhythm.
- **LIGHT PREFERENCE (context decides):** Specific word choices, particular structures, humour placement. When no label exists, assume light preference.

**Natural variation matters:**
- Do not use the same opening formula every time just because it works.
- Do not avoid a word forever just because it is on a banned list (sometimes it is genuinely the right word, e.g. "transparent" when discussing literal transparency in glass, or "robust" in a statistical sense).
- Let the content dictate structure.

**The litmus test:**

> "Does this sound like something Mohd Elfie would actually write, or does it sound like an AI trying very hard to imitate him?"

**The vibe check** (a second pass, after the technical audits):

> Does the prose feel corporately kind, or actually kind? Does it feel like it was generated by someone selling something, or by someone with a stake in what they are saying?

If the prose passes every banned-word and banned-construction check but still reads as machine-generated, the vibe check is the final filter. Trust the gut. If something feels off, it is off, and the cause is usually one of: hollow praise, vacuous content, uncanny-valley word choice, or a templated post shape (Sections 4P through 4T).

If it feels forced, pull back. Inhabit the voice.

---

## 6. Workflow for applying this skill

When drafting English prose for Mohd Elfie:

1. Write the first pass focusing on substance and the rhythm rules in Section 1.
2. Self-audit against Section 3 (banned list). Search the draft for each item in 3A-3G, contractions included. Anything found, rewrite.
3. Run Section 4 patterns. The negative-parallelism check (3F) is the highest-priority audit. For apologetics and sourced work, 4U (canned notability) and 4V (vague attribution) carry extra weight.
4. Read the draft aloud in your head. If it sounds like a corporate blog or a TED talk, scrap and restart.
5. Scan for placeholder artifacts per 4W: square-bracket tokens, all-caps insert strings, `XX` in dates or fields.
6. For narrative, fiction, or any piece building to an emotional or argumentative peak, run the Section 4X restraint sweep as a final pass: set the register phrase, then gloss removal, abstraction to concrete, say-once, defer the reveal, and verify the tonal contract holds at the climax.
7. Return only the polished draft. Do not append "I hope this helps" or explain the editing choices unless asked.

When refining an existing draft the user supplies:

1. Quote-or-paraphrase the worst offenders briefly so the user knows what was caught (banned words, the parallelism count, etc.), but keep the diagnostic short.
2. Return the rewrite as the main deliverable.
3. If the original had a structural problem (e.g. metronome rhythm throughout), say so once. Do not lecture.
**Finalising a long document (papers, chapters): run the triad sweep.**

4B is easy to violate at length. In a manuscript the anaphora piles up exactly where the prose turns rhetorical: perorations, tu quoque lists, the horns of a dilemma. The eye skims its own cadence, so sweep mechanically before delivering. Pull the plain text (for a DOCX, extract the run text first), body and footnotes both:

```python
import re
text = " ".join(open("draft.txt").read().split())

# 1. Anaphoric runs: 3+ consecutive sentences sharing their first two words
sents = re.split(r'(?<=[.;])\s+(?=[A-Z“])', text)
def key(s):
    w = re.findall(r"[A-Za-z’]+", s)[:2]
    return tuple(x.lower() for x in w)
i = 0
while i < len(sents):
    run = 1
    while i+run < len(sents) and len(key(sents[i])) == 2 and key(sents[i]) == key(sents[i+run]):
        run += 1
    if run >= 3:
        print("ANAPHORA:", " / ".join(sents[i:i+run])[:160]); i += run
    else:
        i += 1

# 2. Coordinated triads: "a, b, and/or c", short items
for m in re.finditer(r'([a-z][\w’\- ]{2,38}), ([\w’\- ]{2,38}), (?:and|or) ([\w’\- ]{2,42})', text):
    if all(len(g.split()) <= 3 for g in m.groups()):
        print("TRIAD:", m.group(0)[:90])

# 3. Clause tricolons: "X; Y; and Z."
for m in re.finditer(r'[A-Z][^.;!?]{8,90}; [^.;]{8,90}; and [^.;]{8,110}\.', text):
    print("TRICOLON:", m.group(0)[:160])
```

Judge every hit against the content test in 4B. Collapse the padding. Leave distinct citations, names, technical categories, quotations, and any real analytical frame. Re-run until only the legitimate sets remain. Do this before the final integrity or render check, not after, so a late rewrite cannot reintroduce a tell.

