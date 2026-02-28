"use client";

import { motion } from "framer-motion";
import { useState, useEffect } from "react";

export function EthicsScoreGauge({ score = 94, className = "" }: { score?: number; className?: string }) {
    const [animatedScore, setAnimatedScore] = useState(0);

    useEffect(() => {
        let startTime: number;
        let animationFrame: number;
        const duration = 1500; // 1.5 seconds

        const animate = (time: number) => {
            if (!startTime) startTime = time;
            const progress = Math.min((time - startTime) / duration, 1);
            // Easing out function
            const easeOutQuart = 1 - Math.pow(1 - progress, 4);
            setAnimatedScore(Math.floor(easeOutQuart * score));

            if (progress < 1) {
                animationFrame = requestAnimationFrame(animate);
            } else {
                setAnimatedScore(score);
            }
        };

        // Wait 200ms before starting score animation
        const timeout = setTimeout(() => {
            animationFrame = requestAnimationFrame(animate);
        }, 1000);

        return () => {
            clearTimeout(timeout);
            cancelAnimationFrame(animationFrame);
        };
    }, [score]);

    // M startX,startY A radiusX,radiusY x-axis-rotation large-arc-flag sweep-flag endX,endY
    const pathLength = 251.327; // pi * radius (80)
    const scoreOffset = pathLength - (score / 100) * pathLength;

    return (
        <div className={`relative flex flex-col items-center pt-8 ${className}`}>
            <svg width="240" height="130" viewBox="0 0 240 130" className="overflow-visible">
                {/* Background Arc */}
                <path
                    d="M 20,110 A 100,100 0 0,1 220,110"
                    fill="none"
                    stroke="currentColor"
                    className="text-muted/20"
                    strokeWidth="16"
                    strokeLinecap="round"
                />
                {/* Foreground Arc */}
                <motion.path
                    initial={{ strokeDasharray: pathLength, strokeDashoffset: pathLength }}
                    whileInView={{ strokeDashoffset: scoreOffset }}
                    viewport={{ once: true, margin: "-50px" }}
                    transition={{ duration: 1.5, ease: "easeOut", delay: 0.2 }}
                    d="M 20,110 A 100,100 0 0,1 220,110"
                    fill="none"
                    stroke="url(#gradient)"
                    strokeWidth="16"
                    strokeLinecap="round"
                />
                <defs>
                    <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stopColor="#FF6B6B" /> {/* brand-accent */}
                        <stop offset="50%" stopColor="#F59E0B" /> {/* brand-warning */}
                        <stop offset="100%" stopColor="#10B981" /> {/* brand-success */}
                    </linearGradient>
                </defs>
            </svg>
            <div className="absolute top-[4.5rem] flex flex-col items-center">
                <span className="text-4xl font-bold text-foreground font-mono">
                    <motion.span
                        initial={{ opacity: 0 }}
                        whileInView={{ opacity: 1 }}
                        viewport={{ once: true }}
                        transition={{ duration: 0.5, delay: 0.5 }}
                    >
                        {animatedScore}
                    </motion.span>
                    <span className="text-muted-foreground text-2xl">/100</span>
                </span>
                <span className="text-sm font-semibold text-brand-success mt-1 uppercase tracking-wider">Excellent</span>
            </div>
        </div>
    );
}
