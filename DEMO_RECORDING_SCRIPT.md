# Odradek AI — 2-Minute Demo Recording Script

## Before Recording

### Setup (do these BEFORE pressing record)
1. Open Chrome at `http://localhost:8080/s/contacts`
2. Make sure the Odradek AI panel is visible at the bottom
3. Click **x Clear** to clear any chat history
4. Check the **Plan Mode** checkbox
5. The panel should show "Welcome back" with a clean chat
6. Position Chrome to fill most of the screen (hide any other apps)

### Recording
- Press **Cmd + Shift + 5** to open macOS screen recording
- Select **Record Entire Screen** or **Record Selected Portion** (select Chrome window)
- Click **Record**

---

## Demo Script (2 minutes)

### SCENE 1: Opening (0:00 - 0:10)
**[Show the Contacts page with the AI panel open]**

> *Narration: "This is Odradek AI — an AI assistant built on Mistral that lives inside Mautic, the open-source marketing automation platform. Let me show you a real use case: handling NPS detractors."*

---

### SCENE 2: Create Segment (0:10 - 0:40)
**[Type in the chat input:]**

```
Create a segment called "Last Week Detractors" that filters contacts tagged as "detractor" who were added in the last 7 days
```

**[Press Enter/Send]**

> *Narration: "First, I ask the AI to build a segment of last week's detractors. Watch how it generates an execution plan before taking action."*

**[Wait ~10s for the execution plan to appear]**

> *Narration: "Plan Mode shows exactly what the AI will do — verify tags, create the segment, add filters, and activate it. I approve and it executes."*

**[Click "Approve & Execute"]**

**[Wait ~10s for completion]**

> *Narration: "Segment created. The AI even suggests next steps like creating a follow-up campaign."*

---

### SCENE 3: Create Email (0:40 - 1:20)
**[Re-enable Plan Mode checkbox if unchecked]**
**[Type in the chat input:]**

```
Create an email called "Win Back - Detractors Feb 2025" with subject "We heard you - let's make it right". Tone: warm and apologetic. Acknowledge their frustration, offer a free consultation call with our CEO, and keep it under 150 words.
```

**[Press Enter/Send]**

> *Narration: "Next, I ask it to draft a win-back email. The AI doesn't just execute blindly — it asks smart clarifying questions."*

**[Wait for the plan + clarifying questions to appear]**

> *Narration: "It asks about the CEO's name, whether to include a calendar link, and if we want to add an incentive. This is context-aware AI."*

**[Fill in the fields:]**
- CEO name: `Alex Johnson, our CEO`
- Calendar link: `calendly.com/acme-ceo`
- Incentive: `10% off next purchase`

**[Click "Submit & Execute"]**

**[Wait ~20s for the email to be created]**

> *Narration: "The email is created with the right tone, a Calendly link, and a discount offer — all from a single natural language request."*

---

### SCENE 4: Compliance Check (1:20 - 1:50)
**[Type in the chat input:]**

```
Review this email for CAN-SPAM and GDPR compliance. Check for unsubscribe link, physical address, and sender identification. Flag anything missing.
```

**[Press Enter/Send]**

> *Narration: "Before sending, I ask the AI to run a compliance check — it analyzes the email against CAN-SPAM and GDPR regulations."*

**[Wait ~15s for the compliance report]**

> *Narration: "It found three issues: missing sender email, no physical address, and an unverified unsubscribe link. It even gives an Ethics Score of 85 out of 100 and confirms EU AI Act compliance."*

**[Scroll through the compliance report to show the full analysis]**

---

### SCENE 5: Closing (1:50 - 2:00)
> *Narration: "From identifying at-risk customers to drafting compliant emails — all in under two minutes, all in natural language. This is Odradek AI: AI-powered marketing automation that thinks before it acts."*

---

## After Recording
1. Press **Cmd + Shift + 5** again, then click **Stop** in the menu bar
2. The recording saves to your Desktop by default
3. Trim any dead time (waiting for AI responses) using iMovie or QuickTime:
   - Open in QuickTime > Edit > Trim
   - Speed up waiting sections to 4x

## Key Talking Points for the Jury
- **Plan Mode**: AI shows its plan before executing — transparency and control
- **Clarifying Questions**: AI asks smart follow-up questions instead of guessing
- **Compliance Built-in**: Ethics scoring, CAN-SPAM/GDPR checks, EU AI Act compliance
- **Context Awareness**: AI knows which page you're on and adapts
- **Natural Language**: No coding required — just describe what you want
- **Open Source Stack**: Mautic + Mistral — fully open-source, no vendor lock-in

## Exact Prompts (copy-paste ready)

### Prompt 1 (Segment):
```
Create a segment called "Last Week Detractors" that filters contacts tagged as "detractor" who were added in the last 7 days
```

### Prompt 2 (Email):
```
Create an email called "Win Back - Detractors Feb 2025" with subject "We heard you - let's make it right". Tone: warm and apologetic. Acknowledge their frustration, offer a free consultation call with our CEO, and keep it under 150 words.
```

### Prompt 3 (Compliance):
```
Review this email for CAN-SPAM and GDPR compliance. Check for unsubscribe link, physical address, and sender identification. Flag anything missing.
```

### Clarifying Question Answers:
- CEO name: `Alex Johnson, our CEO`
- Calendar link: `calendly.com/acme-ceo`
- Incentive: `10% off next purchase`
