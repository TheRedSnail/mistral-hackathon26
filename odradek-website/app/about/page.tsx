import { Shield, Globe, HeartHandshake, Wrench } from "lucide-react";
import { Button } from "@/components/ui/button";
import Link from "next/link";

const values = [
    { title: "EU-First", desc: "All data, infrastructure, and compliance is EU-focused. Always.", icon: Globe },
    { title: "Ethics by Design", desc: "Never bolted on as an afterthought. The Guardian Engine is the product, not a feature.", icon: Shield },
    { title: "Open-Core Commitment", desc: "Meaningful free tier. Not a crippled trial. Community matters.", icon: HeartHandshake },
    { title: "Practitioner-Built", desc: "Designed by marketers for marketers. Not by engineers guessing.", icon: Wrench },
];

export default function AboutPage() {
    return (
        <div className="flex flex-col w-full overflow-hidden">
            {/* Mission Statement */}
            <section className="bg-brand-secondary text-white py-24 md:py-32">
                <div className="container max-w-4xl mx-auto px-4 text-center">
                    <h2 className="text-3xl md:text-5xl font-bold tracking-tight mb-12 leading-tight">
                        We believe responsible AI is not a compliance checkbox — <span className="text-brand-primary">it's a competitive advantage.</span>
                    </h2>
                    <div className="text-lg md:text-xl text-white/80 space-y-8 text-left leading-relaxed">
                        <p>
                            ODRADEK was born from frustration. As practitioners who've run global Voice of Customer programs and marketing operations at companies like Henkel and Philips, we watched powerful AI tools arrive with zero ethical scaffolding. We watched regulators accelerate. We watched brands get caught.
                        </p>
                        <p>
                            We named the company after Franz Kafka's <em>"Cares of a Family Man"</em> — Odradek, the strange entity that observes everything and questions everything. Because good marketing should always ask: is this ethical? Is this fair? Is this something our customers would consent to if they truly understood it?
                        </p>
                        <p>
                            We are building the platform we wished existed. One where responsible AI isn't bolted on — it's the engine.
                        </p>
                    </div>
                </div>
            </section>

            {/* Founders */}
            <section className="py-24 bg-brand-surface">
                <div className="container max-w-5xl mx-auto px-4">
                    <h3 className="text-3xl font-bold text-brand-secondary text-center mb-16">Meet the Founders</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-12">
                        {/* Founder 1 */}
                        <div className="bg-white p-8 rounded-2xl border border-brand-border shadow-sm">
                            <div className="w-24 h-24 rounded-full bg-brand-primary/20 flex items-center justify-center text-3xl font-bold text-brand-primary mb-6">ES</div>
                            <h4 className="text-2xl font-bold text-brand-secondary mb-1">Elvis Shehi</h4>
                            <p className="text-brand-primary font-semibold mb-4">Co-Founder & Product Lead</p>
                            <p className="text-brand-text-secondary leading-relaxed mb-6">
                                Global Segmentation & Personalization Lead at Henkel. Former Voice of Customer Lead. MSc Responsible AI (2026), AIGP Certified, PROSCI Certified.
                            </p>
                            <div className="flex flex-wrap gap-2 mt-auto">
                                {["CDP", "Personalization", "AI Ethics", "VoC"].map(tag => (
                                    <span key={tag} className="px-3 py-1 bg-brand-surface text-brand-text-secondary rounded-full text-xs font-medium border border-brand-border/50">{tag}</span>
                                ))}
                            </div>
                        </div>

                        {/* Founder 2 */}
                        <div className="bg-white p-8 rounded-2xl border border-brand-border shadow-sm">
                            <div className="w-24 h-24 rounded-full bg-brand-accent/20 flex items-center justify-center text-3xl font-bold text-brand-accent mb-6">JS</div>
                            <h4 className="text-2xl font-bold text-brand-secondary mb-1">Jan Stoker</h4>
                            <p className="text-brand-accent font-semibold mb-4">Co-Founder & Commercial Lead</p>
                            <p className="text-brand-text-secondary leading-relaxed mb-6">
                                Global B2B Campaign & Journey Manager at Henkel. 12+ years at Philips (Marketing Leadership). MCP / AI Agents Certified.
                            </p>
                            <div className="flex flex-wrap gap-2 mt-auto">
                                {["Journey Orchestration", "B2B Marketing", "Global Ops"].map(tag => (
                                    <span key={tag} className="px-3 py-1 bg-brand-surface text-brand-text-secondary rounded-full text-xs font-medium border border-brand-border/50">{tag}</span>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Values */}
            <section className="py-24 bg-white border-t border-brand-border">
                <div className="container max-w-7xl mx-auto px-4">
                    <h3 className="text-3xl font-bold text-brand-secondary text-center mb-16">Our Core Values</h3>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                        {values.map(val => (
                            <div key={val.title} className="flex flex-col text-center items-center">
                                <div className="w-16 h-16 rounded-2xl bg-brand-primary-light text-brand-primary flex items-center justify-center mb-6">
                                    <val.icon size={32} />
                                </div>
                                <h4 className="text-xl font-bold text-brand-secondary mb-3">{val.title}</h4>
                                <p className="text-brand-text-secondary leading-relaxed text-sm">
                                    {val.desc}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Advisory Board Placeholder */}
            <section className="py-24 bg-brand-surface border-y border-brand-border">
                <div className="container max-w-6xl mx-auto px-4">
                    <h3 className="text-2xl font-bold text-brand-secondary text-center mb-12">Advisory Board</h3>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
                        {["AI Ethics Expert (Academic)", "EU AI Act Legal Specialist", "B2B SaaS GTM Advisor", "Open Source Community Leader"].map((role, i) => (
                            <div key={i} className="bg-white border text-center border-brand-border border-dashed rounded-xl p-6 flex items-center justify-center aspect-square opacity-60">
                                <span className="text-brand-text-secondary text-sm font-medium px-4">{role}</span>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Investors / Funding */}
            <section className="py-24 bg-brand-secondary text-white text-center">
                <div className="container max-w-3xl mx-auto px-4">
                    <h3 className="text-3xl font-bold mb-6">Invest in Responsible AI</h3>
                    <p className="text-xl text-brand-primary-light mb-10 leading-relaxed">
                        We are raising a €750K seed round. If you invest in responsible AI and European MarTech, we'd love to talk.
                    </p>
                    <Button size="lg" className="bg-brand-primary hover:bg-brand-primary-dark text-white font-bold px-8" asChild>
                        <Link href="/contact">Contact Us</Link>
                    </Button>
                </div>
            </section>
        </div>
    );
}
