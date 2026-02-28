"use client";

import { useState } from "react";
import { CheckCircle2, ChevronDown } from "lucide-react";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import { Button } from "@/components/ui/button";

const pricingTiers = [
    {
        name: "Community",
        subtitle: "Open Source",
        monthlyPrice: "Free forever",
        yearlyPrice: "Free forever",
        limit: "50K interactions/mo",
        features: ["Core VoC", "Basic ethics", "Community support"],
        highlight: false
    },
    {
        name: "Starter",
        subtitle: "Growing companies",
        monthlyPrice: "€299/mo",
        yearlyPrice: "€249/mo",
        limit: "100K interactions/mo",
        features: ["Journey builder", "Email support"],
        highlight: false
    },
    {
        name: "Professional",
        subtitle: "Scale-ups",
        monthlyPrice: "€899/mo",
        yearlyPrice: "€749/mo",
        limit: "500K interactions/mo",
        features: ["Compliance hub", "Full API", "Advanced AI"],
        highlight: true,
        badge: "Most Popular"
    },
    {
        name: "Enterprise",
        subtitle: "Large organizations",
        monthlyPrice: "€2,999+/mo",
        yearlyPrice: "€2,499+/mo",
        limit: "Unlimited",
        features: ["Dedicated CSM", "SLA + SSO", "Custom contracts"],
        highlight: false
    }
];

const faqs = [
    {
        question: "What exactly is open-core?",
        answer: "Open-core means the fundamental building blocks of ODRADEK (like the VoC aggregation and baseline ML classifier) are open-source and free forever. Paid tiers add enterprise features like SSO, SLAs, and the advanced drag-and-drop journey builder."
    },
    {
        question: "How do you count 'interactions'?",
        answer: "An interaction is any single customer touchpoint we process or orchestrate. This includes an analyzed survey response, a sent email/SMS via Journey Builder, or a real-time consent verification check."
    },
    {
        question: "Where is my data stored?",
        answer: "For all cloud tiers, your data is stored exclusively in the EU (AWS or GCP frankfurt/amsterdam regions) to ensure strict GDPR compliance. Self-hosted Enterprise customers can deploy on their own infrastructure."
    },
    {
        question: "Can I migrate from Qualtrics or Braze?",
        answer: "Yes. Professional and Enterprise plans include guided onboarding where our team helps you migrate templates, lists, and historical VoC data from legacy platforms via our migration API."
    },
    {
        question: "What happens if I exceed my tier's limit?",
        answer: "We don't hard-stop your marketing. We implement a soft cap where we'll notify you that you've exceeded your monthly volume, giving you 7 days to upgrade to the next tier before any functionality degrades."
    }
];

export function PricingSection() {
    const [isAnnual, setIsAnnual] = useState(true);

    return (
        <section className="bg-brand-surface py-24">
            <div className="container max-w-7xl mx-auto px-4 md:px-8">
                <div className="text-center mb-16">
                    <h2 className="text-4xl md:text-5xl font-bold text-brand-secondary tracking-tight">
                        Open-Core Pricing. <br className="md:hidden" /> <span className="text-brand-primary">Transparent by Design.</span>
                    </h2>
                    <p className="mt-6 text-xl text-brand-text-secondary max-w-2xl mx-auto">
                        Start for free. Grow responsibly.
                    </p>

                    <div className="flex items-center justify-center gap-3 mt-8">
                        <span className={`text-sm font-medium ${!isAnnual ? "text-brand-secondary" : "text-brand-text-secondary"}`}>Monthly</span>
                        <button
                            onClick={() => setIsAnnual(!isAnnual)}
                            className="relative inline-flex h-6 w-11 items-center rounded-full bg-brand-primary transition-colors focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2"
                        >
                            <span className={`inline-block h-4 w-4 transform rounded-full bg-white transition ${isAnnual ? "translate-x-6" : "translate-x-1"}`} />
                        </button>
                        <span className={`text-sm font-medium ${isAnnual ? "text-brand-secondary" : "text-brand-text-secondary"}`}>
                            Annually <span className="ml-1 text-xs text-brand-success font-bold bg-brand-success/10 px-2 py-0.5 rounded-full">2 MONTHS FREE</span>
                        </span>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 max-w-6xl mx-auto mb-24">
                    {pricingTiers.map((tier) => (
                        <div
                            key={tier.name}
                            className={`relative flex flex-col p-8 rounded-2xl transition-transform duration-300 hover:scale-[1.02] ${tier.highlight
                                ? "bg-gradient-to-b from-[#00A088] to-[#007A66] text-white shadow-xl shadow-brand-primary/20 scale-[1.02] z-10 border-none"
                                : "bg-white text-brand-secondary border border-brand-border shadow-sm"
                                }`}
                        >
                            {tier.badge && (
                                <div className="absolute -top-4 left-1/2 -translate-x-1/2 bg-brand-accent text-white text-xs font-bold uppercase tracking-wider py-1 px-3 rounded-full">
                                    {tier.badge}
                                </div>
                            )}

                            <div className="mb-6">
                                <h3 className={`text-xl font-bold ${tier.highlight ? "text-white" : "text-brand-secondary"}`}>{tier.name}</h3>
                                <p className={`text-sm mt-1 ${tier.highlight ? "text-white/80" : "text-brand-text-secondary"}`}>{tier.subtitle}</p>
                            </div>

                            <div className="mb-6">
                                <div className="text-3xl font-bold">
                                    {isAnnual ? tier.yearlyPrice : tier.monthlyPrice}
                                </div>
                                <p className={`text-sm mt-2 font-mono ${tier.highlight ? "text-white/80" : "text-brand-text-secondary"}`}>
                                    {tier.limit}
                                </p>
                            </div>

                            <ul className="flex-1 space-y-4 mb-8">
                                {tier.features.map((feat, i) => (
                                    <li key={i} className="flex items-start gap-3">
                                        <CheckCircle2 size={20} className={`shrink-0 ${tier.highlight ? "text-white" : "text-brand-success"}`} />
                                        <span className={`text-sm ${tier.highlight ? "text-white" : "text-brand-text-primary"}`}>{feat}</span>
                                    </li>
                                ))}
                            </ul>

                            <Button
                                variant={tier.highlight ? "secondary" : "outline"}
                                className={`w-full ${tier.highlight ? "bg-white text-brand-primary hover:bg-gray-50" : "border-brand-border text-brand-secondary hover:bg-gray-50"}`}
                            >
                                {tier.name === "Enterprise" ? "Talk to Sales" : "Get Started"}
                            </Button>
                        </div>
                    ))}
                </div>

                {/* FAQs */}
                <div className="max-w-3xl mx-auto">
                    <h3 className="text-2xl font-bold text-center text-brand-secondary mb-8">Frequently Asked Questions</h3>
                    <Accordion type="single" collapsible className="w-full">
                        {faqs.map((faq, i) => (
                            <AccordionItem key={i} value={`faq-${i}`} className="border-brand-border">
                                <AccordionTrigger className="text-left font-semibold text-brand-secondary hover:text-brand-primary">{faq.question}</AccordionTrigger>
                                <AccordionContent className="text-brand-text-secondary leading-relaxed">
                                    {faq.answer}
                                </AccordionContent>
                            </AccordionItem>
                        ))}
                    </Accordion>
                </div>
            </div>
        </section>
    );
}
