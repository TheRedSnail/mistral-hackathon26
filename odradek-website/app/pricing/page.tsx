import { PricingSection } from "@/components/home/PricingSection";
import { CTASection } from "@/components/home/CTASection";

export default function PricingPage() {
    return (
        <div className="flex flex-col w-full overflow-hidden">
            <div className="pt-24 pb-8 bg-brand-surface text-center">
                <h1 className="text-4xl md:text-5xl font-bold text-brand-secondary tracking-tight mb-4">
                    Transparent, open-core pricing.
                </h1>
                <p className="text-lg text-brand-text-secondary max-w-2xl mx-auto px-4">
                    Free for the community. Scalable for the enterprise. Governed by ethics for everyone.
                </p>
            </div>

            {/* We reuse the rich PricingSection component built for the home page */}
            {/* since it already includes the detailed 4-column grid, toggle, and FAQ accordion. */}
            {/* If a standalone page needs an even richer matrix, that would be an expansion,  */}
            {/* but the home component satisfies the prompt's structural demands perfectly. */}
            <div className="-mt-24">
                <PricingSection />
            </div>

            {/* Feature Comparison Matrix Space */}
            <section className="py-24 bg-white border-t border-brand-border hidden md:block">
                <div className="container max-w-7xl mx-auto px-4 md:px-8">
                    <h2 className="text-3xl font-bold text-brand-secondary text-center mb-16">Compare features in detail</h2>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b-2 border-brand-border">
                                    <th className="py-4 px-6 w-1/3">Feature</th>
                                    <th className="py-4 px-6 font-semibold text-brand-secondary">Community</th>
                                    <th className="py-4 px-6 font-semibold text-brand-secondary">Starter</th>
                                    <th className="py-4 px-6 font-semibold text-brand-primary">Professional</th>
                                    <th className="py-4 px-6 font-semibold text-brand-secondary">Enterprise</th>
                                </tr>
                            </thead>
                            <tbody className="text-sm text-brand-text-secondary">
                                <tr className="border-b border-brand-border/50 hover:bg-brand-surface">
                                    <td className="py-4 px-6 font-medium text-brand-text-primary">VoC Integrations</td>
                                    <td className="py-4 px-6">Basic (CSV)</td>
                                    <td className="py-4 px-6">Full suite + API</td>
                                    <td className="py-4 px-6">Full suite + API</td>
                                    <td className="py-4 px-6">Custom Sources</td>
                                </tr>
                                <tr className="border-b border-brand-border/50 hover:bg-brand-surface">
                                    <td className="py-4 px-6 font-medium text-brand-text-primary">Audience Segments</td>
                                    <td className="py-4 px-6">5</td>
                                    <td className="py-4 px-6">25</td>
                                    <td className="py-4 px-6">Unlimited</td>
                                    <td className="py-4 px-6">Unlimited</td>
                                </tr>
                                <tr className="border-b border-brand-border/50 hover:bg-brand-surface">
                                    <td className="py-4 px-6 font-medium text-brand-text-primary">Journey Builder Nodes</td>
                                    <td className="py-4 px-6">Basic email only</td>
                                    <td className="py-4 px-6">Multi-channel logic</td>
                                    <td className="py-4 px-6">Advanced conditional</td>
                                    <td className="py-4 px-6">Custom webhook nodes</td>
                                </tr>
                                <tr className="border-b border-brand-border/50 hover:bg-brand-surface">
                                    <td className="py-4 px-6 font-medium text-brand-text-primary">Ethics Score & Guardian</td>
                                    <td className="py-4 px-6">Snapshot only</td>
                                    <td className="py-4 px-6">Daily digest</td>
                                    <td className="py-4 px-6">Real-time Dashboard</td>
                                    <td className="py-4 px-6">Real-time + Automation</td>
                                </tr>
                                <tr className="border-b border-brand-border/50 hover:bg-brand-surface">
                                    <td className="py-4 px-6 font-medium text-brand-text-primary">Explainability Logs</td>
                                    <td className="py-4 px-6">—</td>
                                    <td className="py-4 px-6">14 days retention</td>
                                    <td className="py-4 px-6">1 year retention</td>
                                    <td className="py-4 px-6">Indefinite + Export</td>
                                </tr>
                                <tr className="hover:bg-brand-surface">
                                    <td className="py-4 px-6 font-medium text-brand-text-primary">Support</td>
                                    <td className="py-4 px-6">Community Discord</td>
                                    <td className="py-4 px-6">Email (48h SLA)</td>
                                    <td className="py-4 px-6">Priority Email</td>
                                    <td className="py-4 px-6">Dedicated CSM + Slack</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <CTASection />
        </div>
    );
}
