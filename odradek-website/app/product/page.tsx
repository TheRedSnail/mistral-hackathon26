"use client";

import { motion } from "framer-motion";
import { CTASection } from "@/components/home/CTASection";
import { EthicsScoreGauge } from "@/components/EthicsScoreGauge";
import { BarChart3, Workflow, ShieldCheck, CheckCircle2, Lock, Activity, Users, Database } from "lucide-react";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";

export default function ProductPage() {
    return (
        <div className="flex flex-col w-full overflow-hidden">
            {/* Hero */}
            <section className="relative w-full py-24 md:py-32 flex flex-col items-center bg-brand-secondary text-white text-center">
                <div className="container max-w-4xl mx-auto px-4 z-10">
                    <motion.h1
                        initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5 }}
                        className="text-4xl md:text-6xl font-bold tracking-tight mb-6"
                    >
                        Everything your marketing team needs. <span className="text-brand-primary">Governed by ethics from day one.</span>
                    </motion.h1>
                    <motion.p
                        initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5, delay: 0.1 }}
                        className="text-xl text-white/70 max-w-2xl mx-auto mb-10"
                    >
                        Explore the three integrated pillars of the ODRADEK platform. Built from the ground up to empower marketers and protect consumers.
                    </motion.p>
                </div>
                <div className="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.05)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.05)_1px,transparent_1px)] bg-[size:40px_40px] [mask-image:radial-gradient(ellipse_at_center,black_40%,transparent_100%)] pointer-events-none" />
            </section>

            {/* VoC Analytics */}
            <section id="voc" className="py-24 bg-white border-b border-brand-border">
                <div className="container max-w-7xl mx-auto px-4 md:px-8">
                    <div className="flex flex-col lg:flex-row gap-16 items-center">
                        <div className="flex-1 space-y-6">
                            <div className="inline-flex items-center justify-center p-3 bg-brand-primary/10 rounded-xl text-brand-primary mb-4">
                                <BarChart3 size={32} />
                            </div>
                            <h2 className="text-3xl md:text-4xl font-bold text-brand-secondary">VoC Analytics Engine</h2>
                            <p className="text-lg text-brand-text-secondary leading-relaxed">
                                True VoC requires aggregating signals from everywhere, but doing so often creates privacy liabilities. Our engine solves this by ingesting surveys, social, support tickets, and reviews, while redacting PII instantly.
                            </p>
                            <ul className="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
                                {[
                                    "Sentiment Analysis", "NPS Tracking", "Topic Extraction", "PII Redaction",
                                    "Trend Detection", "Customer Health Scores", "Multi-Source Aggregation"
                                ].map(feat => (
                                    <li key={feat} className="flex items-center gap-3 text-brand-text-primary font-medium">
                                        <CheckCircle2 className="text-brand-success shrink-0" size={18} /> {feat}
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <div className="flex-1 w-full relative">
                            <div className="aspect-video rounded-xl bg-brand-surface border border-brand-border p-6 shadow-sm flex flex-col items-center justify-center text-center">
                                <p className="font-mono text-brand-text-secondary mb-4">[Mockup UI Placeholder]</p>
                                <div className="w-full max-w-sm flex flex-col gap-2">
                                    <div className="h-4 bg-brand-border rounded w-full" />
                                    <div className="h-4 bg-brand-border rounded w-5/6 mx-auto" />
                                    <div className="h-32 bg-brand-primary/10 border border-brand-primary/20 rounded mt-4 flex items-end justify-around p-2">
                                        <div className="w-6 bg-brand-success h-[40%] rounded-t-sm" />
                                        <div className="w-6 bg-brand-success h-[60%] rounded-t-sm" />
                                        <div className="w-6 bg-brand-success h-[45%] rounded-t-sm" />
                                        <div className="w-6 bg-brand-warning h-[30%] rounded-t-sm" />
                                        <div className="w-6 bg-brand-success h-[80%] rounded-t-sm" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Journey Builder */}
            <section id="journey" className="py-24 bg-brand-surface border-b border-brand-border">
                <div className="container max-w-7xl mx-auto px-4 md:px-8">
                    <div className="flex flex-col lg:flex-row-reverse gap-16 items-center">
                        <div className="flex-1 space-y-6">
                            <div className="inline-flex items-center justify-center p-3 bg-brand-accent/10 rounded-xl text-brand-accent mb-4">
                                <Workflow size={32} />
                            </div>
                            <h2 className="text-3xl md:text-4xl font-bold text-brand-secondary">Journey Builder</h2>
                            <p className="text-lg text-brand-text-secondary leading-relaxed">
                                Visualize every step of your customer's experience. Our drag-and-drop orchestration canvas supports complex cross-channel logic with built-in ethical guardrails actively evaluating every branch.
                            </p>
                            <ul className="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
                                {[
                                    "Visual Context Canvas", "Event & Time Triggers", "Segment Conditions", "Omnichannel Actions",
                                    "A/B Testing", "Ethical Guardrails", "Execution Monitoring"
                                ].map(feat => (
                                    <li key={feat} className="flex items-center gap-3 text-brand-text-primary font-medium">
                                        <CheckCircle2 className="text-brand-accent shrink-0" size={18} /> {feat}
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <div className="flex-1 w-full relative">
                            <div className="aspect-video rounded-xl bg-white border border-brand-border p-6 shadow-sm flex flex-col items-center justify-center">
                                <div className="relative w-full h-full flex flex-col items-center justify-center gap-4">
                                    <div className="px-4 py-2 bg-brand-secondary text-white rounded shadow text-sm font-medium z-10 w-48 text-center">Trigger: Sign Up</div>
                                    <div className="w-px h-8 bg-brand-border" />
                                    <div className="px-4 py-2 border border-brand-border bg-white rounded shadow text-sm font-medium z-10 w-48 text-center flex items-center justify-center gap-2">
                                        <ShieldCheck size={14} className="text-brand-success" /> Logic: Wait 2 Days
                                    </div>
                                    <div className="w-px h-8 bg-brand-border" />
                                    <div className="px-4 py-2 border border-brand-border bg-white rounded shadow text-sm font-medium z-10 w-48 text-center flex items-center justify-center gap-2">
                                        Email: Onboarding Step 1
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Guardian Engine */}
            <section id="guardian" className="py-24 bg-white border-b border-brand-border">
                <div className="container max-w-7xl mx-auto px-4 md:px-8">
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <div className="inline-flex items-center justify-center p-3 bg-brand-success/10 rounded-xl text-brand-success mb-6">
                            <ShieldCheck size={40} />
                        </div>
                        <h2 className="text-4xl md:text-5xl font-bold text-brand-secondary tracking-tight">The Guardian Engine</h2>
                        <p className="text-xl text-brand-text-secondary mt-6">
                            Our flagship platform differentiator. It sits beneath the entire suite, passively monitoring, proactively blocking, and actively explaining every AI-driven decision.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
                        <div className="col-span-1 md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-6">
                            {[
                                { title: "Dark Pattern Detection", desc: "ML classifier strictly monitors UI logic for manipulative patterns like hidden opt-outs." },
                                { title: "Bias Scanner", desc: "Analyzes automated demographic targeting rules for statistical discrimination." },
                                { title: "Consent Verification", desc: "Hard-checks cross-referenced consent matrices before every outbound touchpoint is fired." },
                                { title: "Frequency Capping", desc: "Platform-wide limits prevent aggressive message fatigue across independent journeys." },
                                { title: "Explainability Reports", desc: "Generates human-readable compliance logs for every algorithmic decision to satisfy auditors." },
                            ].map(f => (
                                <div key={f.title} className="bg-brand-surface border border-brand-border rounded-xl p-6 hover:border-brand-primary/30 transition-colors">
                                    <h4 className="font-bold text-brand-secondary text-lg mb-2">{f.title}</h4>
                                    <p className="text-brand-text-secondary text-sm leading-relaxed">{f.desc}</p>
                                </div>
                            ))}
                        </div>
                        <div className="col-span-1 bg-brand-secondary rounded-xl p-8 flex flex-col items-center justify-center shadow-xl border border-white/10 relative overflow-hidden text-center">
                            <h4 className="text-white/80 font-medium mb-2 relative z-10">Live Platform Ethics Score</h4>
                            <div className="dark relative z-10 w-full flex justify-center scale-90 sm:scale-100">
                                <EthicsScoreGauge score={94} />
                            </div>
                            <div className="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-brand-primary/20 to-transparent pointer-events-none" />
                        </div>
                    </div>
                </div>
            </section>

            {/* Compliance / Integrations / Specs */}
            <section className="py-24 bg-brand-surface">
                <div className="container max-w-7xl mx-auto px-4 md:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-16">

                        {/* Integrations Placeholder */}
                        <div>
                            <h3 className="text-2xl font-bold text-brand-secondary mb-8">Data Sources & Actions</h3>
                            <div className="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                {["Salesforce", "HubSpot", "Klaviyo", "Mailchimp", "Zendesk", "Segment"].map(int => (
                                    <div key={int} className="bg-white border border-brand-border rounded-xl aspect-[3/2] flex flex-col items-center justify-center p-4 relative opacity-60 grayscale hover:grayscale-0 hover:opacity-100 transition-all cursor-not-allowed">
                                        <span className="font-semibold text-brand-text-secondary">{int}</span>
                                        <span className="absolute -top-2 -right-2 bg-brand-accent text-white text-[10px] px-2 py-0.5 rounded-full font-bold shadow-sm whitespace-nowrap z-10">Coming Soon</span>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Specs Accordion */}
                        <div>
                            <h3 className="text-2xl font-bold text-brand-secondary mb-8">Technical & Compliance Specs</h3>
                            <Accordion type="single" collapsible className="w-full bg-white rounded-xl border border-brand-border px-6">
                                <AccordionItem value="item-1" className="border-brand-border">
                                    <AccordionTrigger className="text-left font-semibold text-brand-secondary hover:text-brand-primary">EU AI Act Monitoring</AccordionTrigger>
                                    <AccordionContent className="text-brand-text-secondary leading-relaxed">Continuous scoring of internal ML models against EU AI Act standard criteria for high-risk applications.</AccordionContent>
                                </AccordionItem>
                                <AccordionItem value="item-2" className="border-brand-border">
                                    <AccordionTrigger className="text-left font-semibold text-brand-secondary hover:text-brand-primary">Data Residency & Infrastructure</AccordionTrigger>
                                    <AccordionContent className="text-brand-text-secondary leading-relaxed">Multi-tenant SaaS deployed on AWS/GCP exclusively within EU boundaries (Frankfurt/Amsterdam) guaranteeing no cross-border transit.</AccordionContent>
                                </AccordionItem>
                                <AccordionItem value="item-3" className="border-brand-border">
                                    <AccordionTrigger className="text-left font-semibold text-brand-secondary hover:text-brand-primary">Security & Access</AccordionTrigger>
                                    <AccordionContent className="text-brand-text-secondary leading-relaxed">API-first architecture. Enterprise SSO (SAML/OIDC). Role-Based Access Control (RBAC). SOC 2 Type II audit in progress.</AccordionContent>
                                </AccordionItem>
                                <AccordionItem value="item-4" className="border-brand-border border-b-0">
                                    <AccordionTrigger className="text-left font-semibold text-brand-secondary hover:text-brand-primary">Enterprise SLA</AccordionTrigger>
                                    <AccordionContent className="text-brand-text-secondary leading-relaxed">99.9% financially backed uptime guarantee for Enterprise tier customers.</AccordionContent>
                                </AccordionItem>
                            </Accordion>
                        </div>
                    </div>
                </div>
            </section>

            {/* Reusable CTA */}
            <CTASection />
        </div>
    );
}
