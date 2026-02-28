import fs from "fs";
import path from "path";
import matter from "gray-matter";

const contentDirectory = path.join(process.cwd(), "content", "blog");

export interface PostMetadata {
    title: string;
    date: string;
    author: string;
    excerpt: string;
    category: string;
    slug: string;
}

export interface Post {
    metadata: PostMetadata;
    content: string;
}

export function getPostBySlug(slug: string): Post | null {
    try {
        const realSlug = slug.replace(/\.mdx$/, "");
        const fullPath = path.join(contentDirectory, `${realSlug}.mdx`);
        const fileContents = fs.readFileSync(fullPath, "utf8");
        const { data, content } = matter(fileContents);

        return {
            metadata: { ...data, slug: realSlug } as PostMetadata,
            content,
        };
    } catch (error) {
        return null;
    }
}

export function getAllPosts(): Post[] {
    if (!fs.existsSync(contentDirectory)) return [];

    const slugs = fs.readdirSync(contentDirectory);
    const posts = slugs
        .map((slug) => getPostBySlug(slug))
        .filter((post) => post !== null) as Post[];

    // Sort posts by date in descending order
    return posts.sort((post1, post2) => (post1.metadata.date > post2.metadata.date ? -1 : 1));
}
