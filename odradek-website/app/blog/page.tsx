import Link from "next/link";
import { ArrowRight, Calendar, User } from "lucide-react";
import { CTASection } from "@/components/home/CTASection";
import { getAllPosts } from "@/lib/mdx";

export const metadata = {
    title: "Blog | ODRADEK",
    description: "Insights on responsible AI marketing and compliance.",
};

export default function BlogIndexPage() {
    const posts = getAllPosts();

    return (
        <div className="flex flex-col w-full overflow-hidden">
            <section className="bg-brand-surface py-24 border-b border-brand-border text-center">
                <div className="container max-w-4xl mx-auto px-4">
                    <h1 className="text-4xl md:text-5xl font-bold text-brand-secondary tracking-tight mb-6">
                        The Responsible Marketing Blog
                    </h1>
                    <p className="text-lg md:text-xl text-brand-text-secondary max-w-2xl mx-auto">
                        Insights, compliance updates, and engineering deep-dives from the ODRADEK team.
                    </p>
                </div>
            </section>

            <section className="py-24 bg-white min-h-[50vh]">
                <div className="container max-w-5xl mx-auto px-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                        {posts.map((post) => (
                            <Link
                                key={post.metadata.slug}
                                href={`/blog/${post.metadata.slug}`}
                                className="group flex flex-col bg-white border border-brand-border rounded-2xl p-8 hover:border-brand-primary/50 hover:shadow-lg transition-all"
                            >
                                <div className="flex items-center gap-2 mb-4">
                                    <span className="px-3 py-1 bg-brand-primary/10 text-brand-primary text-xs font-bold uppercase tracking-wider rounded-full">
                                        {post.metadata.category}
                                    </span>
                                </div>
                                <h2 className="text-2xl font-bold text-brand-secondary mb-3 group-hover:text-brand-primary transition-colors">
                                    {post.metadata.title}
                                </h2>
                                <p className="text-brand-text-secondary leading-relaxed mb-8 flex-1">
                                    {post.metadata.excerpt}
                                </p>
                                <div className="flex items-center justify-between text-sm text-brand-text-secondary pt-6 border-t border-brand-border mt-auto">
                                    <div className="flex items-center gap-4">
                                        <span className="flex items-center gap-1.5"><Calendar size={14} /> {post.metadata.date}</span>
                                        <span className="flex items-center gap-1.5"><User size={14} /> {post.metadata.author}</span>
                                    </div>
                                    <ArrowRight size={16} className="text-brand-primary transform group-hover:translate-x-1 transition-transform" />
                                </div>
                            </Link>
                        ))}
                    </div>
                </div>
            </section>

            <CTASection />
        </div>
    );
}
