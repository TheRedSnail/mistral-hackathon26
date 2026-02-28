"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { CheckCircle2 } from "lucide-react";

export function CTASection() {
    const [email, setEmail] = useState("");
    const [submitted, setSubmitted] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (email) setSubmitted(true);
    };

    return (
        <section className="w-full bg-gradient-to-br from-[#00A088] to-[#007A66] py-24 md:py-32 relative overflow-hidden">
            {/* Abstract circles */}
            <div className="absolute inset-0 pointer-events-none overflow-hidden">
                <div className="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-white/5 blur-3xl" />
                <div className="absolute bottom-0 -left-12 w-64 h-64 rounded-full bg-black/10 blur-2xl" />
            </div>

            <div className="container max-w-4xl mx-auto px-4 md:px-8 text-center relative z-10">
                <h2 className="text-4xl md:text-5xl font-bold text-white tracking-tight mb-6 leading-tight">
                    Start Responsible. <br className="md:hidden" /> Grow Confidently.
                </h2>

                <p className="text-lg md:text-xl text-brand-primary-light mb-10 max-w-2xl mx-auto">
                    Join the waitlist for early access. No credit card required. Cancel anytime.
                </p>

                {!submitted ? (
                    <form onSubmit={handleSubmit} className="flex flex-col sm:flex-row gap-3 max-w-md mx-auto mb-8">
                        <Input
                            type="email"
                            placeholder="name@company.com"
                            required
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            className="h-14 bg-white/10 border-white/20 text-white placeholder:text-white/50 focus-visible:ring-white/50"
                        />
                        <Button type="submit" size="lg" className="h-14 px-8 bg-white hover:bg-white/90 text-brand-primary font-bold shadow-xl">
                            Join Waitlist
                        </Button>
                    </form>
                ) : (
                    <div className="flex flex-col items-center gap-3 mb-8 bg-black/10 p-6 rounded-xl border border-white/10 max-w-md mx-auto">
                        <CheckCircle2 className="text-white w-10 h-10" />
                        <p className="text-white font-medium text-lg">You're on the list!</p>
                        <p className="text-white/70 text-sm">We'll be in touch soon.</p>
                    </div>
                )}

                <div className="flex flex-wrap items-center justify-center gap-x-6 gap-y-3 text-sm text-brand-primary-light/80 font-medium">
                    <span className="flex items-center gap-2">
                        <div className="w-1.5 h-1.5 rounded-full bg-white/50" /> EU data residency
                    </span>
                    <span className="flex items-center gap-2">
                        <div className="w-1.5 h-1.5 rounded-full bg-white/50" /> GDPR compliant
                    </span>
                    <span className="flex items-center gap-2">
                        <div className="w-1.5 h-1.5 rounded-full bg-white/50" /> Open core
                    </span>
                </div>
            </div>
        </section>
    );
}
