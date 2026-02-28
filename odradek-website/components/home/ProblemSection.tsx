"use client";

import { motion } from "framer-motion";
import { Gavel, MousePointerClick, BoxSelect, Users, Puzzle, TrendingUp } from "lucide-react";

const problems = [
    {
        title: "Regulatory Tsunami",
        desc: "EU AI Act effective Aug 2025. Fines up to 7% of global revenue. Most martech is unprepared.",
        icon: Gavel,
        highlight: false
    },
    {
        title: "Dark Pattern Epidemic",
        desc: "67% of retail sites use manipulative design. Consumer trust is eroding. Regulators are cracking down.",
        icon: MousePointerClick,
        highlight: false
    },
    {
        title: "Black-Box AI",
        desc: "Personalization engines are opaque. You can't explain why AI targeted someone, and compliance demands you can.",
        icon: BoxSelect,
        highlight: false
    },
    {
        title: "Undetected Bias",
        desc: "AI-driven segmentation often discriminates against demographic groups without companies knowing.",
        icon: Users,
        highlight: false
    },
    {
        title: "Fragmented Tools",
        desc: "VoC, analytics, personalization, compliance — stitched together with duct tape. No unified responsible AI layer.",
        icon: Puzzle,
        highlight: false
    },
    {
        title: "The Gap",
        desc: "NO existing platform combines AI marketing power with built-in ethics, bias detection, and compliance. Until now.",
        icon: TrendingUp,
        highlight: true
    }
];

const containerVariants = {
    hidden: { opacity: 0 },
    visible: {
        opacity: 1,
        transition: { staggerChildren: 0.1 }
    }
};

const cardVariants = {
    hidden: { opacity: 0, y: 20 },
    visible: { opacity: 1, y: 0, transition: { duration: 0.5 } }
};

export function ProblemSection() {
    return (
        <section className="bg-white py-24 relatiive z-20">
            <div className="container max-w-7xl mx-auto px-4 md:px-8">
                <div className="text-center mb-16">
                    <h2 className="text-4xl md:text-5xl font-bold text-brand-secondary tracking-tight">
                        Marketing AI is Powerful. <span className="text-brand-accent">But It&apos;s Broken.</span>
                    </h2>
                    <p className="mt-4 text-xl text-brand-text-secondary max-w-3xl mx-auto pr-4">
                        The old way of bolting on compliance as an afterthought no longer works.
                    </p>
                </div>

                <motion.div
                    className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                    variants={containerVariants}
                    initial="hidden"
                    whileInView="visible"
                    viewport={{ once: true, margin: "-100px" }}
                >
                    {problems.map((prob, idx) => {
                        const Icon = prob.icon;
                        return (
                            <motion.div
                                key={idx}
                                variants={cardVariants}
                                className={`relative overflow-hidden rounded-xl border p-8 flex flex-col gap-4 shadow-sm hover:shadow-md transition-shadow
                  ${prob.highlight
                                        ? "bg-brand-secondary border-brand-secondary text-white"
                                        : "bg-brand-card border-brand-border border-l-4 border-l-brand-accent"
                                    }`}
                            >
                                {!prob.highlight && (
                                    <div className="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                                        <Icon size={120} className="text-brand-accent" />
                                    </div>
                                )}
                                <div className={`w-12 h-12 rounded-lg flex items-center justify-center 
                  ${prob.highlight ? "bg-white/10 text-white" : "bg-brand-accent/10 text-brand-accent"}`}>
                                    <Icon size={24} />
                                </div>
                                <div>
                                    <h3 className={`font-semibold text-xl mb-2 ${prob.highlight ? "text-white" : "text-brand-text-primary"}`}>
                                        {prob.title}
                                    </h3>
                                    <p className={`leading-relaxed ${prob.highlight ? "text-white/80" : "text-brand-text-secondary"}`}>
                                        {prob.desc}
                                    </p>
                                </div>
                            </motion.div>
                        )
                    })}
                </motion.div>
            </div>
        </section>
    );
}
