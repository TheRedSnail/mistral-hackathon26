"use client";

import { CheckCircle2, Download } from "lucide-react";
import { Button } from "@/components/ui/button";

const compliances = [
    { title: "GDPR", status: "Day 1", done: true },
    { title: "EU AI Act (Aug 2025)", status: "Ready", done: true },
    { title: "SOC 2 Type II", status: "In progress", done: false },
    { title: "ISO 27001", status: "Roadmap", done: false },
    { title: "CCPA", status: "US expansion", done: false },
];

export function ComplianceSection() {
    return (
        <section className="bg-brand-secondary py-24 relative overflow-hidden">
            {/* Background grid pattern */}
            <div
                className="absolute inset-0 opacity-10 pointer-events-none"
                style={{ backgroundImage: "linear-gradient(#ffffff 1px, transparent 1px), linear-gradient(90deg, #ffffff 1px, transparent 1px)", backgroundSize: "40px 40px" }}
            />
            <div className="container max-w-7xl mx-auto px-4 md:px-8 relative z-10 flex flex-col items-center">
                <h2 className="text-3xl md:text-5xl font-bold text-white text-center tracking-tight mb-20 max-w-4xl leading-tight">
                    Built for the EU AI Act. <br className="hidden md:block" /> Ready for GDPR. <span className="text-brand-primary">Ahead of Every Deadline.</span>
                </h2>

                {/* Timeline/Badge Grid */}
                <div className="flex flex-wrap justify-center gap-4 md:gap-8 mb-24 w-full max-w-5xl">
                    {compliances.map((item, i) => (
                        <div
                            key={i}
                            className={`flex items-center gap-4 px-6 py-4 rounded-xl border ${item.done ? "bg-brand-success/10 border-brand-success/30" : "bg-white/5 border-white/10"} backdrop-blur-sm shadow-xl`}
                        >
                            <div className="shrink-0">
                                {item.done ? (
                                    <CheckCircle2 className="text-brand-success" size={24} />
                                ) : (
                                    <div className="w-6 h-6 rounded-full border-2 border-dashed border-white/30 flex items-center justify-center">
                                        <div className="w-2 h-2 rounded-full bg-white/30" />
                                    </div>
                                )}
                            </div>
                            <div className="flex flex-col">
                                <span className={`font-semibold ${item.done ? "text-white" : "text-white/60"}`}>{item.title}</span>
                                <span className={`text-sm ${item.done ? "text-brand-success" : "text-white/40"}`}>{item.status}</span>
                            </div>
                        </div>
                    ))}
                </div>

                {/* Callout Quote */}
                <div className="max-w-4xl mx-auto text-center mb-16 relative">
                    <span className="absolute -top-12 -left-12 text-8xl text-brand-primary/20 font-serif leading-none select-none">"</span>
                    <p className="text-2xl md:text-3xl italic text-brand-primary font-medium leading-relaxed font-serif">
                        "Marketing AI systems that can't explain their decisions will not survive EU AI Act enforcement."
                    </p>
                    <span className="absolute -bottom-8 -right-8 text-8xl text-brand-primary/20 font-serif leading-none select-none">"</span>
                </div>

                {/* CTA */}
                <Button size="lg" className="bg-white hover:bg-gray-100 text-brand-secondary font-medium px-8 py-6 text-lg relative group">
                    <Download className="mr-2 h-5 w-5" /> Download our EU AI Act Readiness Guide &rarr;
                </Button>
            </div>
        </section>
    );
}
