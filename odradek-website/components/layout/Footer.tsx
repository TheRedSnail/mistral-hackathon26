import Link from "next/link";
import { Github, Twitter, Linkedin } from "lucide-react";
import { Logo } from "./Navbar";

export function Footer() {
    return (
        <footer className="border-t bg-background">
            <div className="container max-w-7xl mx-auto px-4 md:px-8 pt-16 pb-8">
                <div className="grid grid-cols-1 md:grid-cols-4 gap-12 mb-16">
                    {/* Col 1 */}
                    <div className="space-y-4 md:col-span-1">
                        <div className="pointer-events-none">
                            <Logo />
                        </div>
                        <p className="text-sm text-brand-text-secondary mt-4">
                            The Guardian Engine for Responsible Marketing. Built for EU AI Act compliance.
                        </p>
                    </div>

                    {/* Col 2 */}
                    <div>
                        <h3 className="font-semibold text-brand-text-primary mb-4">Product</h3>
                        <ul className="space-y-3 text-sm text-brand-text-secondary">
                            <li><Link href="/product#voc" className="hover:text-brand-primary transition">VoC Analytics</Link></li>
                            <li><Link href="/product#journey" className="hover:text-brand-primary transition">Journey Builder</Link></li>
                            <li><Link href="/product#guardian" className="hover:text-brand-primary transition">Guardian Engine</Link></li>
                            <li><Link href="/pricing" className="hover:text-brand-primary transition">Pricing</Link></li>
                        </ul>
                    </div>

                    {/* Col 3 */}
                    <div>
                        <h3 className="font-semibold text-brand-text-primary mb-4">Resources</h3>
                        <ul className="space-y-3 text-sm text-brand-text-secondary">
                            <li><Link href="/docs" className="hover:text-brand-primary transition">Documentation</Link></li>
                            <li><Link href="/blog" className="hover:text-brand-primary transition">Blog</Link></li>
                            <li><Link href="/mission" className="hover:text-brand-primary transition">Our Mission</Link></li>
                            <li><Link href="/community" className="hover:text-brand-primary transition">Community</Link></li>
                        </ul>
                    </div>

                    {/* Col 4 */}
                    <div>
                        <h3 className="font-semibold text-brand-text-primary mb-4">Company</h3>
                        <ul className="space-y-3 text-sm text-brand-text-secondary">
                            <li><Link href="/about" className="hover:text-brand-primary transition">About Us</Link></li>
                            <li><Link href="/contact" className="hover:text-brand-primary transition">Contact</Link></li>
                            <li><Link href="/contact" className="hover:text-brand-primary transition">Sales & Investors</Link></li>
                        </ul>
                    </div>
                </div>

                <div className="flex flex-col md:flex-row items-center justify-between pt-8 border-t border-border gap-6">
                    <div className="flex flex-col md:flex-row items-center gap-4 md:gap-8 text-sm text-brand-text-secondary mt-4 md:mt-0">
                        <span>© 2026 ODRADEK B.V.</span>
                        <div className="flex flex-wrap justify-center gap-4">
                            <Link href="#" className="hover:text-brand-primary transition">Privacy Policy</Link>
                            <Link href="#" className="hover:text-brand-primary transition">Terms of Service</Link>
                            <Link href="#" className="hover:text-brand-primary transition">Cookie Preferences</Link>
                        </div>
                    </div>
                    <div className="flex flex-wrap justify-center items-center gap-4 text-brand-primary text-xs font-mono">
                        <span>EU AI Act Ready</span>
                        <span>·</span>
                        <span>GDPR Compliant</span>
                        <span>·</span>
                        <span>Open Core</span>
                    </div>
                    <div className="flex gap-4 text-brand-text-secondary mt-4 md:mt-0">
                        <Link href="https://twitter.com" target="_blank" rel="noopener noreferrer" className="hover:text-brand-primary transition"><Twitter size={20} /></Link>
                        <Link href="https://linkedin.com" target="_blank" rel="noopener noreferrer" className="hover:text-brand-primary transition"><Linkedin size={20} /></Link>
                        <Link href="https://github.com/odradekai/odradek" target="_blank" rel="noopener noreferrer" className="hover:text-brand-primary transition"><Github size={20} /></Link>
                    </div>
                </div>
            </div>
        </footer>
    );
}
