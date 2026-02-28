import { getPostBySlug, getAllPosts } from "@/lib/mdx";
import { notFound } from "next/navigation";
import { Calendar, User, ArrowLeft } from "lucide-react";
import Link from "next/link";
import { MDXRemote } from "next-mdx-remote/rsc";

export async function generateStaticParams() {
    const posts = getAllPosts();
    return posts.map((post) => ({
        slug: post.metadata.slug,
    }));
}

export default async function BlogPostPage({ params }: { params: { slug: string } }) {
    const post = getPostBySlug(params.slug);

    if (!post) {
        return notFound();
    }

    return (
        <article className="bg-white min-h-screen pb-32">
            <div className="container max-w-3xl mx-auto px-4 pt-16">
                <Link href="/blog" className="inline-flex items-center gap-2 text-brand-primary font-medium hover:underline mb-12">
                    <ArrowLeft size={16} /> Back to Blog
                </Link>

                <header className="mb-12">
                    <span className="px-3 py-1 bg-brand-primary/10 text-brand-primary text-xs font-bold uppercase tracking-wider rounded-full mb-6 inline-block">
                        {post.metadata.category}
                    </span>
                    <h1 className="text-4xl md:text-5xl font-bold text-brand-secondary tracking-tight mb-6 leading-tight">
                        {post.metadata.title}
                    </h1>
                    <div className="flex items-center gap-6 text-brand-text-secondary text-sm border-b border-brand-border pb-8">
                        <span className="flex items-center gap-2 font-medium">
                            <div className="w-8 h-8 rounded-full bg-brand-surface flex items-center justify-center border border-brand-border">
                                <User size={14} />
                            </div>
                            {post.metadata.author}
                        </span>
                        <span className="flex items-center gap-1.5">
                            <Calendar size={14} /> {post.metadata.date}
                        </span>
                    </div>
                </header>

                <div className="prose prose-slate prose-lg max-w-none prose-headings:text-brand-secondary prose-a:text-brand-primary hover:prose-a:text-brand-primary-dark prose-p:text-brand-text-secondary prose-p:leading-relaxed prose-strong:text-brand-secondary prose-li:text-brand-text-secondary">
                    <MDXRemote source={post.content} />
                </div>
            </div>
        </article>
    );
}
