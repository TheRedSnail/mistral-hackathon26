"use client";

import { useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { BarChart3, Workflow, ShieldCheck, ArrowRight, CheckCircle2 } from "lucide-react";
import Link from "next/link";
import { Button } from "@/components/ui/button";

const pillars = [
    {
        id: "voc",
        title: "VoC Analytics Engine",
        icon: BarChart3,
        description: "Multi-source aggregation and AI-powered insight extraction without the privacy risks.",
        features: [
            "Aggregate surveys (NPS, CSAT, CES), social, and support tickets",
            "Real-time AI sentiment analysis & topic extraction",
            "Automatic PII redaction at ingestion",
            "Predictive customer health scores"
        ]
    },
    {
        id: "journey",
        title: "Journey Orchestration",
        icon: Workflow,
        description: "Visual drag-and-drop journey builder with ethics guardrails baked into every node.",
        features: [
            "Cross-channel triggers (email, SMS, push, webhook)",
            "Ethics guardrails active at every node",
            "4 built-in ethical templates (e.g., Consent Renewal)",
            "A/B testing governed by demographic fairness"
        ]
    },
    {
        id: "guardian",
        title: "Guardian Engine",
        icon: ShieldCheck,
        description: "The core differentiator: Real-time ethical AI monitoring for your entire platform.",
        features: [
            "Dark pattern ML classifier blocks manipulative UI",
            "Demographic bias scanner for audience segments",
            "Consent verification before every single touchpoint",
            "Global Ethics Score (0-100) reflecting ethical health"
        ]
    }
];

export function SolutionSection() {
    const [activeTab, setActiveTab] = useState(pillars[0].id);
    const activePillar = pillars.find(p => p.id === activeTab)!;

    return (
        <section className="bg-brand-primary-light py-24 relative overflow-hidden">
            {/* Background Decorative element */}
            <div className="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none opacity-50">
                <div className="absolute -top-[10%] -right-[5%] w-[50%] h-[50%] rounded-full bg-brand-primary/10 blur-[100px]" />
                <div className="absolute -bottom-[10%] -left-[5%] w-[50%] h-[50%] rounded-full bg-brand-primary/10 blur-[100px]" />
            </div>

            <div className="container max-w-7xl mx-auto px-4 md:px-8 relative z-10">
                <div className="text-center mb-16">
                    <h2 className="text-4xl md:text-5xl font-bold text-brand-secondary tracking-tight">
                        One Platform. <br className="md:hidden" /> Three Integrated Pillars.
                    </h2>
                    <p className="mt-4 text-xl text-brand-text-secondary max-w-2xl mx-auto">
                        Stop stitching together fragmented tools. Manage experience, orchestration, and compliance in one place.
                    </p>
                </div>

                <div className="flex flex-col lg:flex-row gap-8 lg:gap-16 items-start">
                    {/* Tabs */}
                    <div className="flex flex-col w-full lg:w-1/3 gap-4">
                        {pillars.map((pillar) => {
                            const Icon = pillar.icon;
                            const isActive = activeTab === pillar.id;

                            return (
                                <button
                                    key={pillar.id}
                                    onClick={() => setActiveTab(pillar.id)}
                                    className={`flex items-start gap-4 p-6 rounded-2xl text-left transition-all duration-300 relative overflow-hidden group
                    ${isActive
                                            ? "bg-white shadow-xl ring-1 ring-black/5"
                                            : "bg-white/40 hover:bg-white/60 hover:shadow-md"
                                        }`}
                                >
                                    {isActive && (
                                        <motion.div
                                            layoutId="activeTab"
                                            className="absolute inset-0 border-l-4 border-brand-primary bg-white rounded-2xl"
                                            initial={false}
                                            transition={{ type: "spring", stiffness: 300, damping: 30 }}
                                        />
                                    )}
                                    <div className="relative z-10 flex items-start gap-4">
                                        <div className={`p-3 rounded-lg flex items-center justify-center shrink-0 transition-colors
                      ${isActive ? "bg-brand-primary text-white" : "bg-brand-primary/10 text-brand-primary group-hover:bg-brand-primary/20"}`}>
                                            <Icon size={24} />
                                        </div>
                                        <div>
                                            <h3 className={`text-xl font-bold mb-1 transition-colors ${isActive ? "text-brand-secondary" : "text-brand-text-primary"}`}>
                                                {pillar.title}
                                            </h3>
                                            {isActive && (
                                                <motion.p
                                                    initial={{ opacity: 0, height: 0 }}
                                                    animate={{ opacity: 1, height: "auto" }}
                                                    className="text-brand-text-secondary text-sm mt-2 leading-relaxed"
                                                >
                                                    {pillar.description}
                                                </motion.p>
                                            )}
                                        </div>
                                    </div>
                                </button>
                            );
                        })}
                    </div>

                    {/* Tab Content */}
                    <div className="w-full lg:w-2/3 bg-white rounded-3xl p-8 md:p-12 shadow-2xl relative overflow-hidden min-h-[400px] border border-brand-primary/10">
                        <AnimatePresence mode="wait">
                            <motion.div
                                key={activeTab}
                                initial={{ opacity: 0, x: 20 }}
                                animate={{ opacity: 1, x: 0 }}
                                exit={{ opacity: 0, x: -20 }}
                                transition={{ duration: 0.3 }}
                                className="h-full flex flex-col justify-center"
                            >
                                <div className="flex items-center gap-4 mb-8">
                                    <div className="p-4 bg-brand-primary-light rounded-xl text-brand-primary">
                                        {(() => { const Icon = activePillar.icon; return <Icon size={32} />; })()}
                                    </div>
                                    <h3 className="text-3xl font-bold text-brand-secondary">{activePillar.title}</h3>
                                </div>

                                <p className="text-lg text-brand-text-secondary mb-8 leading-relaxed max-w-2xl">
                                    {activePillar.description}
                                </p>

                                <ul className="space-y-4 mb-10 w-full sm:w-5/6">
                                    {activePillar.features.map((feature, i) => (
                                        <motion.li
                                            key={i}
                                            initial={{ opacity: 0, y: 10 }}
                                            animate={{ opacity: 1, y: 0 }}
                                            transition={{ delay: 0.1 * i, duration: 0.4 }}
                                            className="flex items-start gap-3 text-brand-text-primary font-medium"
                                        >
                                            <CheckCircle2 className="text-brand-success shrink-0 mt-0.5" size={20} />
                                            <span className="leading-snug">{feature}</span>
                                        </motion.li>
                                    ))}
                                </ul>

                                <Button className="w-max bg-brand-secondary hover:bg-brand-secondary/90" asChild>
                                    <Link href={`/product#${activePillar.id}`}>
                                        Learn more <ArrowRight className="ml-2" size={16} />
                                    </Link>
                                </Button>
                            </motion.div>
                        </AnimatePresence>
                    </div>
                </div>
            </div>

            {/* Full-width sticky-style banner at the bottom */}
            <div className="absolute bottom-0 left-0 w-full bg-brand-secondary py-4 px-4 text-center">
                <p className="text-white font-medium text-sm md:text-base tracking-wide">
                    <span className="text-brand-primary font-bold">FIRST</span> platform combining AI marketing power + ethics + compliance in a single solution.
                </p>
            </div>
        </section>
    );
}
