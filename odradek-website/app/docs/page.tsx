import Link from "next/link";
import { ArrowRight } from "lucide-react";

export default function DocsIndexPage() {
    return (
        <div className="prose prose-slate max-w-4xl prose-h1:text-4xl prose-h1:font-bold prose-h1:text-brand-secondary prose-a:text-brand-primary">
            <h1>Introduction</h1>
            <p className="text-xl text-brand-text-secondary mb-8">
                Welcome to the official ODRADEK documentation. ODRADEK is the Guardian Engine for Responsible Marketing.
            </p>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-12 not-prose">
                <Link href="/docs/installation" className="border border-brand-border rounded-xl p-6 bg-brand-surface hover:border-brand-primary transition-colors group">
                    <h3 className="font-bold text-brand-secondary mb-2 flex items-center justify-between">
                        Installation <ArrowRight size={16} className="text-brand-primary transform group-hover:translate-x-1 transition-transform" />
                    </h3>
                    <p className="text-brand-text-secondary text-sm">Set up ODRADEK locally using Docker Compose.</p>
                </Link>
                <Link href="#" className="border border-brand-border rounded-xl p-6 bg-brand-surface hover:border-brand-primary transition-colors group">
                    <h3 className="font-bold text-brand-secondary mb-2 flex items-center justify-between">
                        Core Concepts <ArrowRight size={16} className="text-brand-primary transform group-hover:translate-x-1 transition-transform" />
                    </h3>
                    <p className="text-brand-text-secondary text-sm">Learn about the Guardian Engine and Ethics Score.</p>
                </Link>
            </div>
        </div>
    );
}
