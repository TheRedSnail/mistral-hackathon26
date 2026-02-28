"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Sheet, SheetContent, SheetTrigger, SheetTitle } from "@/components/ui/sheet";
import { Menu } from "lucide-react";

export function Logo({ className }: { className?: string }) {
    return (
        <Link href="/" className={`flex items-center gap-2 ${className}`}>
            <svg
                width="28"
                height="28"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                className="text-brand-primary"
            >
                <path
                    d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
                <path
                    d="M12 8c-2.5 0-4.5 2-4.5 4s2 4 4.5 4 4.5-2 4.5-4-2-4-4.5-4z"
                    stroke="currentColor"
                    strokeWidth="1.5"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
                <circle cx="12" cy="12" r="1.5" fill="currentColor" />
            </svg>
            <span className="font-bold text-xl tracking-[0.25em] text-brand-primary">
                ODRADEK
            </span>
        </Link>
    );
}

const navLinks = [
    { name: "Product", href: "/product" },
    { name: "Pricing", href: "/pricing" },
    { name: "Community", href: "/community" },
    { name: "Blog", href: "/blog" },
    { name: "About", href: "/about" },
];

export function Navbar() {
    const pathname = usePathname();

    return (
        <header className="sticky top-0 z-50 w-full border-b border-border/40 bg-background/80 backdrop-blur-md">
            <div className="container flex h-16 max-w-7xl mx-auto items-center justify-between px-4 md:px-8">
                <Logo />

                {/* Desktop Nav */}
                <nav className="hidden md:flex items-center gap-6">
                    {navLinks.map((link) => (
                        <Link
                            key={link.name}
                            href={link.href}
                            className={`text-sm font-medium transition-colors hover:text-brand-primary ${pathname === link.href ? "text-brand-primary" : "text-foreground/80"
                                }`}
                        >
                            {link.name}
                        </Link>
                    ))}
                </nav>

                <div className="hidden md:flex items-center gap-4">
                    <Button variant="ghost" className="hidden sm:flex" asChild>
                        <Link href="/login">Sign In</Link>
                    </Button>
                    <Button className="bg-brand-primary hover:bg-brand-primary-dark text-white" asChild>
                        <Link href="/signup">Start for free</Link>
                    </Button>
                </div>

                {/* Mobile Nav */}
                <div className="md:hidden flex items-center gap-4">
                    <Sheet>
                        <SheetTrigger asChild>
                            <Button variant="ghost" size="icon" className="md:hidden">
                                <Menu className="h-5 w-5" />
                                <span className="sr-only">Toggle Menu</span>
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="right" className="pr-0">
                            <SheetTitle className="sr-only">Mobile Menu</SheetTitle>
                            <Logo className="mb-8" />
                            <div className="flex flex-col gap-4">
                                {navLinks.map((link) => (
                                    <Link
                                        key={link.name}
                                        href={link.href}
                                        className="text-lg font-medium text-foreground hover:text-brand-primary"
                                    >
                                        {link.name}
                                    </Link>
                                ))}
                                <div className="h-px bg-border my-4 mr-6" />
                                <Link href="/login" className="text-lg font-medium">
                                    Sign In
                                </Link>
                                <Link href="/signup" className="text-lg font-medium text-brand-primary">
                                    Start for free
                                </Link>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>
            </div>
        </header>
    );
}
