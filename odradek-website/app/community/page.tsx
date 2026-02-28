"use client";

import { Github, MessageSquare, Star, Mail } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import Link from "next/link";
import { useState } from "react";

export default function CommunityPage() {
    const [email, setEmail] = useState("");

    const handleNewsletter = (e: React.FormEvent) => {
        e.preventDefault();
        if (email) setEmail("");
    };

    return (
        <div className="flex flex-col w-full overflow-hidden pb-32">
            {/* Hero */}
            <section className="bg-brand-surface py-24 border-b border-brand-border text-center">
                <div className="container max-w-4xl mx-auto px-4">
                    <h1 className="text-4xl md:text-5xl font-bold text-brand-secondary tracking-tight mb-6">
                        Join the Responsible Marketing Movement.
                    </h1>
                    <p className="text-lg md:text-xl text-brand-text-secondary max-w-2xl mx-auto">
                        ODRADEK is open-core because ethics should be transparent. Our community is at the heart of our mission to build a privacy-first marketing ecosystem.
                    </p>
                </div>
            </section>

            {/* GitHub & Discord Cards */}
            <section className="py-24 container max-w-6xl mx-auto px-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-24">
                    {/* GitHub Card */}
                    <div className="bg-white border border-brand-border rounded-2xl p-8 flex flex-col shadow-sm hover:shadow-md transition-shadow">
                        <div className="flex items-center justify-between mb-6">
                            <div className="w-12 h-12 rounded-xl bg-brand-surface border border-brand-border flex items-center justify-center">
                                <Github size={24} className="text-brand-secondary" />
                            </div>
                            <div className="flex items-center gap-1.5 bg-brand-surface border border-brand-border px-3 py-1.5 rounded-md text-sm font-semibold text-brand-secondary">
                                <Star size={16} className="text-brand-warning fill-brand-warning" /> 1,204
                            </div>
                        </div>
                        <h3 className="text-2xl font-bold text-brand-secondary mb-3">⭐ Star us on GitHub</h3>
                        <p className="text-brand-text-secondary leading-relaxed mb-8 flex-1">
                            Our core VoC engine, Guardian Engine base classifier, and journey templates are entirely open-source. Help us build the ultimate compliance layer for modern marketing.
                        </p>
                        <Button className="w-full bg-brand-secondary hover:bg-brand-secondary/90 text-white" asChild>
                            <Link href="https://github.com/odradekai/odradek" target="_blank">View Repository</Link>
                        </Button>
                    </div>

                    {/* Discord Card */}
                    <div className="bg-[#5865F2]/10 border border-[#5865F2]/20 rounded-2xl p-8 flex flex-col shadow-sm hover:shadow-md transition-shadow">
                        <div className="flex items-center justify-between mb-6">
                            <div className="w-12 h-12 rounded-xl bg-[#5865F2] flex items-center justify-center text-white">
                                <MessageSquare size={24} />
                            </div>
                        </div>
                        <h3 className="text-2xl font-bold text-[#5865F2] mb-3">Join our Discord</h3>
                        <p className="text-brand-text-secondary leading-relaxed mb-8 flex-1">
                            Ask questions, share feedback, and help shape the roadmap. Hang out with our engineering team, beta customers, and fellow responsible AI advocates.
                        </p>
                        <Button className="w-full bg-[#5865F2] hover:bg-[#4752C4] text-white asChild">
                            <Link href="https://discord.com" target="_blank">Join the Server</Link>
                        </Button>
                    </div>
                </div>

                {/* Contribution / Roadmap Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-12">

                    {/* Contribution Guide */}
                    <div className="lg:col-span-1 border-r border-brand-border pr-8">
                        <h4 className="text-2xl font-bold text-brand-secondary mb-6">Contribution Guide</h4>
                        <ul className="space-y-4 text-brand-text-secondary text-sm">
                            <li className="bg-brand-surface px-4 py-3 rounded-lg border border-brand-border">
                                <strong>Workflow:</strong> Fork → Branch → PR
                            </li>
                            <li className="bg-brand-surface px-4 py-3 rounded-lg border border-brand-border">
                                <strong>Code Style:</strong> Python (Black + isort); TypeScript (ESLint + Prettier)
                            </li>
                            <li className="bg-brand-surface px-4 py-3 rounded-lg border border-brand-border">
                                <Link href="/docs" className="text-brand-primary font-semibold hover:underline">How to add a new tool/integration &rarr;</Link>
                            </li>
                            <li className="bg-brand-surface px-4 py-3 rounded-lg border border-brand-border">
                                <Link href="/docs" className="text-brand-primary font-semibold hover:underline">Contributing to the Guardian Engine &rarr;</Link>
                            </li>
                            <li className="bg-brand-surface px-4 py-3 rounded-lg border border-brand-border">
                                <Link href="https://github.com" className="text-brand-success font-semibold flex items-center gap-2 hover:underline">
                                    <span className="w-2 h-2 rounded-full bg-brand-success" /> Explore "Good first issues"
                                </Link>
                            </li>
                        </ul>
                    </div>

                    {/* Roadmap */}
                    <div className="lg:col-span-2">
                        <h4 className="text-2xl font-bold text-brand-secondary mb-6">Community Roadmap</h4>
                        <div className="border border-brand-border rounded-xl bg-white overflow-x-auto shadow-sm">
                            {/* Fake GitHub Project Board Layout */}
                            <div className="flex min-w-[600px] h-[400px]">
                                <div className="w-1/3 border-r border-brand-border bg-brand-surface/50 p-4">
                                    <div className="flex items-center justify-between mb-4">
                                        <span className="text-sm font-semibold text-brand-text-primary px-2 py-1 bg-brand-surface border border-brand-border rounded-md shadow-sm">Planned <span className="text-brand-text-secondary ml-1 bg-white px-1.5 rounded-full border border-brand-border text-xs">3</span></span>
                                    </div>
                                    <div className="space-y-3">
                                        <div className="bg-white border border-brand-border rounded-lg p-3 text-sm text-brand-text-secondary shadow-sm">HubSpot Bi-directional Sync</div>
                                        <div className="bg-white border border-brand-border rounded-lg p-3 text-sm text-brand-text-secondary shadow-sm">Custom LLM support for Guardian</div>
                                        <div className="bg-white border border-brand-border rounded-lg p-3 text-sm text-brand-text-secondary shadow-sm">Webhook Journey Triggers</div>
                                    </div>
                                </div>
                                <div className="w-1/3 border-r border-brand-border bg-brand-surface/50 p-4">
                                    <div className="flex items-center justify-between mb-4">
                                        <span className="text-sm font-semibold text-brand-text-primary px-2 py-1 bg-brand-surface border border-brand-border rounded-md shadow-sm">In Progress <span className="text-brand-text-secondary ml-1 bg-white px-1.5 rounded-full border border-brand-border text-xs">2</span></span>
                                    </div>
                                    <div className="space-y-3">
                                        <div className="bg-white border border-brand-border rounded-lg p-3 text-sm text-brand-text-secondary shadow-sm border-l-4 border-l-brand-warning">Multi-Language Sentiment (fr, de)</div>
                                        <div className="bg-white border border-brand-border rounded-lg p-3 text-sm text-brand-text-secondary shadow-sm border-l-4 border-l-brand-warning">Klaviyo API Integration V1</div>
                                    </div>
                                </div>
                                <div className="w-1/3 bg-brand-surface/50 p-4">
                                    <div className="flex items-center justify-between mb-4">
                                        <span className="text-sm font-semibold text-brand-text-primary px-2 py-1 bg-brand-surface border border-brand-border rounded-md shadow-sm">Done <span className="text-brand-text-secondary ml-1 bg-white px-1.5 rounded-full border border-brand-border text-xs">3</span></span>
                                    </div>
                                    <div className="space-y-3">
                                        <div className="bg-white border border-brand-border border-l-4 border-l-brand-success rounded-lg p-3 text-sm text-brand-text-secondary shadow-sm line-through opacity-70">Docker Compose Self-Hosting</div>
                                        <div className="bg-white border border-brand-border border-l-4 border-l-brand-success rounded-lg p-3 text-sm text-brand-text-secondary shadow-sm line-through opacity-70">Base Dark Pattern ML Model</div>
                                        <div className="bg-white border border-brand-border border-l-4 border-l-brand-success rounded-lg p-3 text-sm text-brand-text-secondary shadow-sm line-through opacity-70">Visual Journey Canvas Alpha</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Newsletter */}
            <section className="bg-brand-primary-light border border-brand-primary/20 rounded-3xl container max-w-4xl mx-auto px-8 py-16 text-center">
                <div className="w-16 h-16 rounded-2xl bg-white text-brand-primary flex items-center justify-center mx-auto mb-6 shadow-sm border border-brand-primary/10">
                    <Mail size={32} />
                </div>
                <h3 className="text-2xl font-bold text-brand-secondary mb-4">Responsible AI in Marketing — Monthly Insights</h3>
                <p className="text-brand-text-secondary mb-8 max-w-lg mx-auto">
                    Get our newsletter covering EU AI Act updates, dark pattern case studies, and practical bias detection techniques.
                </p>
                <form onSubmit={handleNewsletter} className="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
                    <Input
                        type="email"
                        placeholder="Your email address"
                        required
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        className="bg-white border-brand-primary/20 h-12 focus-visible:ring-brand-primary"
                    />
                    <Button type="submit" className="bg-brand-primary hover:bg-brand-primary-dark text-white font-bold h-12 px-8 shadow-md">
                        Subscribe
                    </Button>
                </form>
            </section>
        </div>
    );
}
