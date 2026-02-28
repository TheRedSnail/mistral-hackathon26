"use client";

import { motion } from "framer-motion";
import { LineChart, Mic, MessageSquare, AlertTriangle, Blocks, Workflow, Users, ShieldCheck } from "lucide-react";

export function FeaturesSection() {
    return (
        <section className="bg-white py-24 md:py-32 overflow-hidden">
            <div className="container max-w-7xl mx-auto px-4 md:px-8 flex flex-col gap-32">
                {/* Feature 1: Listen Smarter */}
                <div className="flex flex-col lg:flex-row items-center gap-12 lg:gap-20">
                    <div className="w-full lg:w-1/2 flex flex-col gap-6">
                        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-primary/10 text-brand-primary font-semibold text-sm w-max">
                            <Mic size={16} /> VOC Analytics
                        </div>
                        <h2 className="text-3xl md:text-4xl font-bold text-brand-secondary">Listen Smarter. <br /> Understand Deeper.</h2>
                        <p className="text-lg text-brand-text-secondary leading-relaxed">
                            Aggregate customer feedback from surveys, social media, support tickets, and reviews. Our AI extracts sentiment and topics in real-time, instantly surfacing what matters without manual tagging.
                        </p>
                        <ul className="flex flex-col gap-3 mt-2">
                            <li className="flex items-center gap-3 text-brand-text-primary"><div className="w-1.5 h-1.5 rounded-full bg-brand-primary" /> Automatic PII redaction at ingestion</li>
                            <li className="flex items-center gap-3 text-brand-text-primary"><div className="w-1.5 h-1.5 rounded-full bg-brand-primary" /> Multi-source predictive health scoring</li>
                        </ul>
                    </div>
                    <div className="w-full lg:w-1/2 relative">
                        <MockupContainer>
                            {/* VoC Mockup Content */}
                            <div className="flex items-center justify-between mb-6">
                                <h4 className="text-white/80 font-medium">Sentiment Trend</h4>
                                <select className="bg-brand-secondary/50 text-white/60 text-xs border border-white/10 rounded px-2 py-1"><option>Last 30 Days</option></select>
                            </div>
                            <div className="flex gap-4 items-end h-32 w-full border-b border-white/10 pb-2">
                                {[30, 45, 60, 50, 75, 80, 70, 85].map((h, i) => (
                                    <motion.div
                                        key={i}
                                        className="flex-1 rounded-t-sm"
                                        style={{ height: `${h}%`, background: i > 4 ? '#10B981' : '#F59E0B' }}
                                        initial={{ scaleY: 0 }}
                                        whileInView={{ scaleY: 1 }}
                                        viewport={{ once: true }}
                                        transition={{ duration: 0.6, delay: 0.1 * i }}
                                    />
                                ))}
                            </div>
                            <div className="mt-6 flex flex-col gap-3">
                                <div className="bg-white/5 rounded p-3 flex items-start gap-3">
                                    <MessageSquare className="text-brand-success shrink-0" size={16} />
                                    <p className="text-xs text-white/70 leading-relaxed">&quot;The [REDACTED] feature is absolutely incredible. Saved my team [REDACTED] hours this week.&quot;</p>
                                </div>
                            </div>
                        </MockupContainer>
                    </div>
                </div>

                {/* Feature 2: Journey with Ethics */}
                <div className="flex flex-col lg:flex-row-reverse items-center gap-12 lg:gap-20">
                    <div className="w-full lg:w-1/2 flex flex-col gap-6">
                        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-accent/10 text-brand-accent font-semibold text-sm w-max">
                            <Workflow size={16} /> Journey Orchestration
                        </div>
                        <h2 className="text-3xl md:text-4xl font-bold text-brand-secondary">Journey with Ethics. <br /> Design with Confidence.</h2>
                        <p className="text-lg text-brand-text-secondary leading-relaxed">
                            Build cross-channel marketing flows with our visual drag-and-drop canvas. Uniquely, ethics guardrails are baked into every node, preventing message fatigue and demographic discrimination automatically.
                        </p>
                        <ul className="flex flex-col gap-3 mt-2">
                            <li className="flex items-center gap-3 text-brand-text-primary"><div className="w-1.5 h-1.5 rounded-full bg-brand-accent" /> 4 built-in ethical templates included</li>
                            <li className="flex items-center gap-3 text-brand-text-primary"><div className="w-1.5 h-1.5 rounded-full bg-brand-accent" /> Granular consent verifications</li>
                        </ul>
                    </div>
                    <div className="w-full lg:w-1/2 relative">
                        <MockupContainer>
                            {/* Journey Builder Mockup Content */}
                            <div className="relative h-full flex flex-col items-center py-4">
                                <motion.div initial={{ y: -20, opacity: 0 }} whileInView={{ y: 0, opacity: 1 }} className="flex items-center gap-2 bg-brand-secondary/80 border border-white/20 p-2 rounded shadow-lg z-10 w-48 justify-center">
                                    <Users size={14} className="text-brand-primary" /> <span className="text-xs font-semibold text-white">Segment: High Churn</span>
                                </motion.div>
                                <div className="w-0.5 h-8 bg-white/20" />
                                <motion.div initial={{ x: -20, opacity: 0 }} whileInView={{ x: 0, opacity: 1 }} transition={{ delay: 0.2 }} className="flex items-center gap-2 bg-brand-secondary/80 border border-brand-accent p-2 rounded shadow-lg z-10 w-48 justify-center relative">
                                    <AlertTriangle size={14} className="text-brand-accent" /> <span className="text-xs font-semibold text-white">Ethics Review: Blocked</span>
                                    <div className="absolute -right-2 top-1/2 -translate-y-1/2 w-4 h-0.5 bg-brand-accent" />
                                    <div className="absolute -right-[80px] top-1/2 -translate-y-1/2 bg-brand-accent/20 border border-brand-accent/50 text-brand-accent text-[9px] px-1.5 py-0.5 rounded leading-tight w-20">Potential Age Bias Detected</div>
                                </motion.div>
                                <div className="w-0.5 h-8 bg-white/20" />
                                <motion.div initial={{ y: 20, opacity: 0 }} whileInView={{ y: 0, opacity: 1 }} transition={{ delay: 0.4 }} className="flex items-center gap-2 bg-brand-secondary/80 border border-white/20 p-2 rounded shadow-lg z-10 w-48 justify-center">
                                    <Blocks size={14} className="text-white/60" /> <span className="text-xs font-semibold text-white/60">A/B Test: Holdout</span>
                                </motion.div>
                            </div>
                        </MockupContainer>
                    </div>
                </div>

                {/* Feature 3: The Guardian Engine */}
                <div className="flex flex-col lg:flex-row items-center gap-12 lg:gap-20">
                    <div className="w-full lg:w-1/2 flex flex-col gap-6">
                        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-success/10 text-brand-success font-semibold text-sm w-max">
                            <ShieldCheck size={16} /> The Guardian Engine
                        </div>
                        <h2 className="text-3xl md:text-4xl font-bold text-brand-secondary">Compliance meets Control. <br /> At scale.</h2>
                        <p className="text-lg text-brand-text-secondary leading-relaxed">
                            Our flagship real-time ethics monitoring dashboard. Featuring an overarching 0-100 Ethics Score that reflects your marketing operation's health, plus dark pattern detection and bias scanning out of the box.
                        </p>
                        <ul className="flex flex-col gap-3 mt-2">
                            <li className="flex items-center gap-3 text-brand-text-primary"><div className="w-1.5 h-1.5 rounded-full bg-brand-success" /> Explainability reports for regulators</li>
                            <li className="flex items-center gap-3 text-brand-text-primary"><div className="w-1.5 h-1.5 rounded-full bg-brand-success" /> Dark pattern ML classifier</li>
                        </ul>
                    </div>
                    <div className="w-full lg:w-1/2 relative">
                        <MockupContainer>
                            {/* Guardian Mockup Content */}
                            <div className="grid grid-cols-2 gap-4 h-full">
                                <div className="col-span-1 bg-white/5 rounded-lg border border-white/10 p-4 flex flex-col items-center justify-center">
                                    <span className="text-xs text-brand-success uppercase font-semibold tracking-wider mb-2">Ethics Score</span>
                                    <span className="text-5xl font-mono font-bold text-white">94</span>
                                    <span className="text-[10px] text-white/50 mt-1">out of 100</span>
                                </div>
                                <div className="col-span-1 flex flex-col gap-4">
                                    <div className="flex-1 bg-brand-accent/10 border border-brand-accent/30 rounded-lg p-3 relative overflow-hidden">
                                        <div className="absolute top-0 left-0 w-1 h-full bg-brand-accent" />
                                        <h5 className="text-[10px] font-semibold text-brand-accent flex items-center gap-1"><AlertTriangle size={10} /> Dark Pattern Blocked</h5>
                                        <p className="text-[10px] text-white/70 mt-1 leading-tight">Confirmed manipulable UI pattern restricted on cart checkout page via JS inject intercept.</p>
                                    </div>
                                    <div className="flex-1 bg-white/5 border border-white/10 rounded-lg p-3">
                                        <h5 className="text-[10px] font-semibold text-brand-primary flex items-center gap-1"><ShieldCheck size={10} /> Bias Scan Clean</h5>
                                        <p className="text-[10px] text-white/70 mt-1 leading-tight">Q3 segmentation analysis shows no demographic discrimination vs baseline.</p>
                                    </div>
                                </div>
                            </div>
                        </MockupContainer>
                    </div>
                </div>
            </div>
        </section>
    );
}

function MockupContainer({ children }: { children: React.ReactNode }) {
    return (
        <div className="relative w-full aspect-video rounded-xl bg-gradient-to-br from-[#1A3A5C] to-[#0D1F30] border border-brand-border/20 shadow-xl overflow-hidden p-6 flex flex-col">
            <div className="absolute top-0 left-0 w-full h-8 bg-[#0D1F30]/80 backdrop-blur-sm border-b border-white/10 flex items-center px-3 gap-1.5">
                <div className="w-2.5 h-2.5 rounded-full bg-red-400" />
                <div className="w-2.5 h-2.5 rounded-full bg-yellow-400" />
                <div className="w-2.5 h-2.5 rounded-full bg-green-400" />
            </div>
            <div className="flex-1 pt-6 overflow-hidden">
                {children}
            </div>
        </div>
    )
}
