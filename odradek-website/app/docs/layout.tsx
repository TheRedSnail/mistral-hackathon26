import Link from "next/link";
import { Search } from "lucide-react";

export default function DocsLayout({ children }: { children: React.ReactNode }) {
    return (
        <div className="flex w-full max-w-7xl mx-auto border-x border-brand-border/50 min-h-screen bg-white">
            {/* Sidebar */}
            <aside className="hidden md:flex flex-col w-64 shrink-0 border-r border-brand-border/50 py-8 px-6 bg-brand-surface sticky top-16 h-[calc(100vh-4rem)] overflow-y-auto">
                <div className="relative mb-8">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-brand-text-secondary" size={16} />
                    <input
                        type="text"
                        placeholder="Search docs..."
                        className="w-full bg-white border border-brand-border rounded-md pl-9 pr-3 py-2 text-sm focus:outline-none focus:border-brand-primary/50"
                        disabled
                    />
                </div>

                <div className="space-y-8">
                    <div>
                        <h4 className="font-semibold text-brand-secondary text-sm mb-3">Getting Started</h4>
                        <ul className="space-y-2 text-sm">
                            <li><Link href="/docs" className="text-brand-text-secondary hover:text-brand-primary">Introduction</Link></li>
                            <li><Link href="/docs/installation" className="text-brand-primary font-medium">Installation</Link></li>
                            <li><Link href="/docs/quick-start" className="text-brand-text-secondary hover:text-brand-primary">Quick Start</Link></li>
                            <li><Link href="/docs/config" className="text-brand-text-secondary hover:text-brand-primary">Configuration</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h4 className="font-semibold text-brand-secondary text-sm mb-3">Core Concepts</h4>
                        <ul className="space-y-2 text-sm">
                            <li><Link href="#" className="text-brand-text-secondary hover:text-brand-primary">VoC Analytics Engine</Link></li>
                            <li><Link href="#" className="text-brand-text-secondary hover:text-brand-primary">Journey Builder</Link></li>
                            <li><Link href="#" className="text-brand-text-secondary hover:text-brand-primary">Guardian Engine</Link></li>
                            <li><Link href="#" className="text-brand-text-secondary hover:text-brand-primary">Ethics Score</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h4 className="font-semibold text-brand-secondary text-sm mb-3">Self-Hosting</h4>
                        <ul className="space-y-2 text-sm">
                            <li><Link href="#" className="text-brand-text-secondary hover:text-brand-primary">Docker Setup</Link></li>
                            <li><Link href="#" className="text-brand-text-secondary hover:text-brand-primary">Environment Variables</Link></li>
                            <li><Link href="#" className="text-brand-text-secondary hover:text-brand-primary">Database Setup</Link></li>
                            <li><Link href="#" className="text-brand-text-secondary hover:text-brand-primary">Production Deployment</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h4 className="font-semibold text-brand-secondary text-sm mb-3">Reference</h4>
                        <ul className="space-y-2 text-sm">
                            <li><Link href="#" className="text-brand-text-secondary hover:text-brand-primary">API Reference</Link></li>
                            <li><Link href="#" className="text-brand-text-secondary hover:text-brand-primary">Changelog</Link></li>
                        </ul>
                    </div>
                </div>
            </aside>

            {/* Main Content */}
            <main className="flex-1 min-w-0 py-8 px-6 md:px-12 bg-white">
                {children}
            </main>
        </div>
    );
}
