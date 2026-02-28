import { Quote } from "lucide-react";

export function SocialProofSection() {
    return (
        <section className="bg-white py-24 border-t border-brand-border/50">
            <div className="container max-w-7xl mx-auto px-4 md:px-8">
                <div className="text-center mb-16">
                    <h2 className="text-3xl md:text-5xl font-bold text-brand-secondary tracking-tight">
                        Built by Practitioners, <br className="md:hidden" /> <span className="text-brand-primary">for Practitioners.</span>
                    </h2>
                    <p className="mt-6 text-lg text-brand-text-secondary max-w-2xl mx-auto">
                        We've run global VoC and personalization programs at the world's largest enterprises. We know where the bodies are buried.
                    </p>
                </div>

                {/* Stats Row */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mb-24 border-y border-brand-border/50 py-12">
                    <div className="text-center flex flex-col items-center">
                        <span className="text-5xl font-bold text-brand-primary font-mono mb-2">15+</span>
                        <span className="text-brand-secondary font-medium uppercase tracking-wider text-sm">Years Enterprise Experience</span>
                    </div>
                    <div className="text-center flex flex-col items-center md:border-x border-brand-border/50">
                        <span className="text-5xl font-bold text-brand-primary font-mono mb-2">$138B+</span>
                        <span className="text-brand-secondary font-medium uppercase tracking-wider text-sm">Addressable Market</span>
                    </div>
                    <div className="text-center flex flex-col items-center">
                        <span className="text-5xl font-bold text-brand-accent font-mono mb-2">Aug '25</span>
                        <span className="text-brand-secondary font-medium uppercase tracking-wider text-sm">EU AI Act Enforcement</span>
                    </div>
                </div>

                {/* Origin Logos */}
                <div className="flex flex-col items-center mb-24">
                    <p className="text-sm text-brand-text-secondary uppercase tracking-widest font-semibold mb-8">Founding team hails from</p>
                    <div className="flex flex-wrap justify-center gap-8 md:gap-16">
                        <div className="h-12 px-6 flex items-center justify-center rounded-lg border-2 border-brand-primary/20 bg-brand-primary/5">
                            <span className="text-xl font-bold text-brand-secondary/70 tracking-tight">Henkel</span>
                        </div>
                        <div className="h-12 px-6 flex items-center justify-center rounded-lg border-2 border-brand-primary/20 bg-brand-primary/5">
                            <span className="text-xl font-bold text-brand-secondary/70 tracking-tight">PHILIPS</span>
                        </div>
                    </div>
                </div>

                {/* Testimonials Placeholder */}
                <div>
                    <h3 className="text-2xl font-bold text-brand-secondary text-center mb-12">Trusted by Early Adopters</h3>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 relative">
                        {/* Coming soon overlay */}
                        <div className="absolute inset-0 z-10 flex items-center justify-center bg-white/60 backdrop-blur-[2px] rounded-xl">
                            <div className="bg-brand-secondary text-white px-6 py-3 rounded-full font-semibold shadow-xl border border-white/20">
                                Coming soon from beta customers
                            </div>
                        </div>

                        {/* Fake Cards */}
                        {[1, 2, 3].map(i => (
                            <div key={i} className="bg-brand-surface border border-brand-border p-6 rounded-2xl opacity-50 select-none">
                                <Quote className="text-brand-primary/40 mb-4" size={32} />
                                <p className="text-brand-text-secondary italic mb-6">
                                    "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam."
                                </p>
                                <div className="flex items-center gap-3">
                                    <div className="w-10 h-10 rounded-full bg-brand-border" />
                                    <div>
                                        <div className="h-4 w-24 bg-brand-border rounded mb-1.5" />
                                        <div className="h-3 w-16 bg-brand-border rounded" />
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}
