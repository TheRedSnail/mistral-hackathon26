"use client";

import { EthicsScoreGauge } from "./EthicsScoreGauge";
import { Activity, ShieldCheck, Mail, Users } from "lucide-react";
import { motion } from "framer-motion";

export function DashboardMockup({ className = "" }: { className?: string }) {
    return (
        <div className={`w-full max-w-4xl mx-auto rounded-xl border border-white/20 bg-[#0D1F30] overflow-hidden shadow-2xl shadow-brand-primary/20 flex flex-col ${className}`}>
            {/* Browser Header */}
            <div className="h-10 border-b border-white/10 flex items-center px-4 gap-2 bg-[#0A1826]">
                <div className="flex gap-1.5">
                    <div className="w-3 h-3 rounded-full bg-red-500/80" />
                    <div className="w-3 h-3 rounded-full bg-yellow-500/80" />
                    <div className="w-3 h-3 rounded-full bg-green-500/80" />
                </div>
                <div className="mx-auto bg-white/5 h-6 rounded-md w-1/3 border border-white/5 flex items-center justify-center">
                    <span className="text-[10px] text-white/40 font-mono">odradekai.com/app/guardian</span>
                </div>
            </div>

            {/* Layout */}
            <div className="flex flex-col sm:flex-row flex-1 min-h-[400px]">
                {/* Sidebar */}
                <div className="w-full sm:w-48 border-r border-white/10 bg-[#0D1F30]/50 p-4 flex flex-row sm:flex-col gap-2 overflow-x-auto sm:overflow-visible">
                    <div className="hidden sm:flex items-center gap-2 text-brand-primary mb-6 mt-2 px-2">
                        <ShieldCheck size={20} />
                        <span className="font-bold text-sm tracking-wider">ODRADEK</span>
                    </div>
                    <SidebarItem icon={Activity} label="Dashboard" />
                    <SidebarItem icon={Users} label="VoC Analytics" />
                    <SidebarItem icon={Mail} label="Journey Builder" />
                    <SidebarItem icon={ShieldCheck} label="Guardian Engine" active />
                </div>

                {/* Main Content */}
                <div className="flex-1 p-6 sm:p-8 bg-[#0D1F30] overflow-hidden">
                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                        <div>
                            <h2 className="text-xl font-bold text-white mb-1">Guardian Engine Overview</h2>
                            <p className="text-sm text-white/50">Real-time ethics monitoring and compliance.</p>
                        </div>
                        <div className="px-3 py-1 pb-1.5 rounded-full bg-brand-success/20 border border-brand-success/30 text-brand-success text-xs font-semibold flex items-center gap-1.5 w-max">
                            <div className="w-1.5 h-1.5 rounded-full bg-brand-success animate-pulse" />
                            MONITORING ACTIVE
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div className="lg:col-span-1 rounded-xl border border-white/10 bg-white/5 p-6 flex flex-col items-center justify-center relative shadow-inner">
                            <h3 className="text-white/70 text-sm font-medium absolute top-4 left-4">Global Ethics Score</h3>
                            {/* Forcing a dark theme for this block specifically to ensure colors POP */}
                            <div className="dark">
                                <EthicsScoreGauge score={94} className="scale-[0.85] transform-origin-top mt-4" />
                            </div>
                        </div>
                        <div className="lg:col-span-2 rounded-xl border border-white/10 bg-white/5 p-6 shadow-inner">
                            <h3 className="text-white/70 text-sm font-medium mb-4">Interactions Analyzed (7d)</h3>
                            {/* CSS Art Line Chart */}
                            <div className="relative h-32 w-full mt-4 flex items-end justify-between gap-1">
                                {[40, 55, 45, 70, 65, 80, 75, 90, 85, 100, 95, 110].map((h, i) => (
                                    <div key={i} className="w-full bg-brand-primary/10 rounded-t-sm relative group overflow-hidden">
                                        <motion.div
                                            initial={{ height: 0 }}
                                            whileInView={{ height: `${Math.min(100, h)}%` }}
                                            viewport={{ once: true }}
                                            transition={{ duration: 1, delay: i * 0.05 }}
                                            className="absolute bottom-0 w-full bg-brand-primary/80 rounded-t-sm"
                                        />
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

function SidebarItem({ icon: Icon, label, active = false }: { icon: any, label: string, active?: boolean }) {
    return (
        <div className={`flex items-center gap-3 px-3 py-2 rounded-md text-sm cursor-pointer transition-colors whitespace-nowrap ${active ? "bg-brand-primary/10 text-brand-primary border-l-2 border-brand-primary pl-2 sm:pl-3" : "text-white/50 hover:text-white hover:bg-white/5 border-l-2 border-transparent"}`}>
            <Icon size={16} className="shrink-0" />
            <span>{label}</span>
        </div>
    )
}
