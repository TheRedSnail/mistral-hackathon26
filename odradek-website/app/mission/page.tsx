import Link from "next/link";
import { ArrowRight } from "lucide-react";
import { Button } from "@/components/ui/button";

export default function MissionPage() {
    return (
        <div className="flex flex-col w-full bg-white pb-24">
            {/* Editorial Header */}
            <div className="bg-brand-surface pt-24 pb-16 border-b border-brand-border">
                <div className="container max-w-3xl mx-auto px-4 md:px-0">
                    <span className="text-brand-primary font-bold tracking-wider uppercase text-sm mb-4 block">Manifesto</span>
                    <h1 className="text-4xl md:text-6xl font-bold text-brand-secondary tracking-tight leading-[1.1] mb-8">
                        Responsible AI Marketing: Why It Matters, Why Now
                    </h1>
                </div>
            </div>

            <article className="container max-w-3xl mx-auto px-4 md:px-0 pt-16 space-y-12 text-lg text-brand-text-primary leading-relaxed">
                {/* Pull quote */}
                <div className="border-l-4 border-brand-primary pl-6 py-2 my-12">
                    <p className="text-2xl md:text-3xl text-brand-primary font-serif italic leading-snug">
                        "The EU AI Act doesn't create the ethics problem. <br /> It just creates the deadline."
                    </p>
                </div>

                {/* Section 1 */}
                <section className="space-y-6">
                    <h2 className="text-2xl md:text-3xl font-bold text-brand-secondary mt-12 mb-6">1. The Regulatory Moment</h2>
                    <p>
                        By August 2025, the EU AI Act will be fully enforceable. It categorizes AI systems by risk, and many AI marketing applications — particularly those involving biometric categorization, emotion recognition, or critical decision-making — will fall under stricter scrutiny.
                    </p>
                    <p>
                        Fines can reach up to <strong>7% of global annual revenue</strong>. Yet, the vast majority of current marketing technology stacks bolt on AI features as "black boxes" with no transparency, no explainability, and no built-in compliance logs. Most platforms are fundamentally unprepared for an era where regulators can demand to see the algorithmic rationale behind a segmented campaign.
                    </p>
                </section>

                {/* Section 2 */}
                <section className="space-y-6">
                    <h2 className="text-2xl md:text-3xl font-bold text-brand-secondary mt-12 mb-6">2. The Trust Crisis</h2>
                    <p>
                        A staggering <strong>67% of retail sites</strong> currently utilize dark patterns — manipulative UI designed to trick users into actions they didn't intend to take (like hidden subscriptions or false scarcity).
                    </p>
                    <p>
                        The consequence of this short-term optimization is a collapse in consumer trust. Ethical marketing is no longer a moral luxury; it is a commercial imperative. Consumers are punishing brands that manipulate them, and regulators are formalizing that punishment into law (such as the EU's Digital Services Act updates).
                    </p>
                </section>

                {/* Section 3 */}
                <section className="space-y-6">
                    <h2 className="text-2xl md:text-3xl font-bold text-brand-secondary mt-12 mb-6">3. The Bias Problem</h2>
                    <p>
                        AI-driven personalization and segmentation algorithms often harbor invisible biases. Because these models optimize for pure conversion metrics, they routinely discriminate against specific demographic groups in housing, finance, and general retail targeting.
                    </p>
                    <p>
                        Without dedicated tooling to scan audiences and holdout groups for statistical bias, marketing teams are flying blind — deploying discriminatory practices they aren't even aware of.
                    </p>
                </section>

                {/* Section 4 */}
                <section className="space-y-6">
                    <h2 className="text-2xl md:text-3xl font-bold text-brand-secondary mt-12 mb-6">4. Our Commitment</h2>
                    <p>
                        ODRADEK is built differently. We believe that privacy and ethics must be embedded at the architecture level, not offered as an optional premium toggle. Our commitments are hard-coded into the platform:
                    </p>
                    <ul className="list-disc pl-6 space-y-3 text-brand-text-secondary">
                        <li><strong>GDPR compliant from Day 1:</strong> Built-in PII redaction and automated right-to-erasure workflows.</li>
                        <li><strong>EU AI Act Ready:</strong> Generates explainable AI rationale reports for regulatory audits.</li>
                        <li><strong>Open-Core Model:</strong> The core aggregation and ML detection layers remain open-source for community auditing.</li>
                    </ul>
                </section>

                {/* Section 5 */}
                <section className="space-y-6">
                    <h2 className="text-2xl md:text-3xl font-bold text-brand-secondary mt-12 mb-6">5. What Responsible AI Looks Like</h2>
                    <p>
                        It looks like a campaign builder that physically prevents you from deploying a dark pattern. It looks like an overarching <strong>Ethics Score (0-100)</strong> that gives the CMO a real-time dashboard of the brand's ethical health. It looks like automated consent verification matrices cross-referencing your CRM before an email leaves the server.
                    </p>
                    <p>
                        It looks like ODRADEK.
                    </p>
                </section>

                {/* Footer CTA */}
                <div className="pt-16 pb-8 border-t border-brand-border mt-16 flex flex-col items-start gap-6">
                    <h3 className="text-2xl font-bold text-brand-secondary">Ready to align your marketing tech with your values?</h3>
                    <Button size="lg" className="bg-brand-primary hover:bg-brand-primary-dark" asChild>
                        <Link href="/product#guardian">See how the Guardian Engine works <ArrowRight className="ml-2 w-4 h-4" /></Link>
                    </Button>
                </div>
            </article>
        </div>
    );
}
