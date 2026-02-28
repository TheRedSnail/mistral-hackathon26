"use client";

import { motion } from "framer-motion";
import { Button } from "@/components/ui/button";
import { Play } from "lucide-react";
import { DashboardMockup } from "@/components/DashboardMockup";

function FloatingParticles() {
    const particles = Array.from({ length: 20 });
    return (
        <div className="absolute inset-0 overflow-hidden pointer-events-none">
            {particles.map((_, i) => {
                const size = Math.random() * 4 + 2;
                const startX = Math.random() * 100;
                const startY = Math.random() * 100;
                const duration = Math.random() * 20 + 10;
                const delay = Math.random() * 5;

                return (
                    <motion.div
                        key={i}
                        className="absolute rounded-full bg-brand-primary opacity-20"
                        style={{ width: size, height: size, left: `${startX}%`, top: `${startY}%` }}
                        animate={{
                            y: [0, -100, 0],
                            x: [0, 50, 0],
                            opacity: [0.1, 0.4, 0.1],
                        }}
                        transition={{
                            duration,
                            delay,
                            repeat: Infinity,
                            ease: "linear",
                        }}
                    />
                );
            })}
        </div>
    );
}

import type { Variants } from "framer-motion";

const containerVariants: Variants = {
    hidden: { opacity: 0 },
    visible: {
        opacity: 1,
        transition: {
            staggerChildren: 0.15,
            delayChildren: 0.2,
        },
    },
};

const itemVariants: Variants = {
    hidden: { opacity: 0, y: 20 },
    visible: { opacity: 1, y: 0, transition: { duration: 0.6, ease: "easeOut" } },
};

export function Hero() {
    return (
        <section className="relative w-full min-h-screen pt-24 pb-16 md:pt-32 md:pb-24 flex flex-col items-center overflow-hidden" style={{ background: "linear-gradient(135deg, #1A3A5C 0%, #0D1F30 100%)" }}>
            <FloatingParticles />

            <div className="container max-w-7xl mx-auto px-4 md:px-8 relative z-10 flex flex-col items-center">
                <motion.div
                    variants={containerVariants}
                    initial="hidden"
                    animate="visible"
                    className="flex flex-col items-center text-center max-w-4xl w-full"
                >
                    {/* Wordmark */}
                    <motion.div variants={itemVariants} className="mb-6 flex items-center justify-center gap-3">
                        <div className="px-4 py-1.5 rounded-full border border-brand-primary/30 bg-brand-primary/10 text-brand-primary text-sm font-semibold tracking-wider flex items-center gap-2">
                            <span className="relative flex h-2 w-2">
                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-primary opacity-75"></span>
                                <span className="relative inline-flex rounded-full h-2 w-2 bg-brand-primary"></span>
                            </span>
                            ODRADEK AI
                        </div>
                    </motion.div>

                    <motion.h1 variants={itemVariants} className="text-5xl md:text-7xl font-bold tracking-tight text-white mb-6 leading-[1.1]">
                        The Engine for Responsible AI in Marketing
                    </motion.h1>

                    <motion.h2 variants={itemVariants} className="text-xl md:text-2xl text-brand-primary font-medium mb-6">
                        AI-Powered · Privacy-First · Ethics-Embedded
                    </motion.h2>

                    <motion.p variants={itemVariants} className="text-lg md:text-xl text-gray-300 mb-10 max-w-2xl leading-relaxed">
                        The first marketing platform that helps you listen better, speak smarter, and grow, with responsible AI at its core.
                    </motion.p>

                    <motion.div variants={itemVariants} className="flex flex-col sm:flex-row gap-4 w-full sm:w-auto mb-12">
                        <Button size="lg" className="bg-brand-primary hover:bg-brand-primary-dark text-white font-medium px-8 py-6 text-lg relative group overflow-hidden">
                            <span className="relative z-10">Start for Free &rarr;</span>
                            <div className="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700 ease-out" />
                        </Button>
                        <Button size="lg" variant="outline" className="border-white/20 bg-white/5 hover:bg-white/10 text-white font-medium px-8 py-6 text-lg">
                            <Play className="mr-2 h-5 w-5 fill-current" /> Watch Demo
                        </Button>
                    </motion.div>

                    <motion.div variants={itemVariants} className="flex flex-wrap justify-center items-center gap-x-8 gap-y-4 text-sm font-medium text-gray-400 mb-20">
                        <span className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-brand-success" /> EU AI Act Ready</span>
                        <span className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-brand-success" /> GDPR Day 1</span>
                        <span className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-brand-success" /> Open Core</span>
                        <span className="flex items-center gap-2"><div className="w-1.5 h-1.5 rounded-full bg-brand-success" /> No credit card</span>
                    </motion.div>
                </motion.div>

                {/* Scroll-triggered mockup */}
                <motion.div
                    initial={{ opacity: 0, y: 100 }}
                    whileInView={{ opacity: 1, y: 0 }}
                    viewport={{ once: true, margin: "-100px" }}
                    transition={{ duration: 1, ease: "easeOut" }}
                    className="w-full relative z-20"
                >
                    <DashboardMockup />
                </motion.div>
            </div>

            {/* Bottom gradient fade into next white section */}
            <div className="absolute bottom-0 left-0 right-0 h-32 bg-gradient-to-t from-background to-transparent pointer-events-none z-10" />
        </section>
    );
}
