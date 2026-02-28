"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { Building2, Mail, MapPin, MessageSquare, ShieldCheck, Phone } from "lucide-react";

import { Button } from "@/components/ui/button";
import {
    Form,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";

// Schema definition
const formSchema = z.object({
    name: z.string().min(2, { message: "Name must be at least 2 characters." }),
    email: z.string().email({ message: "Invalid work email address." }),
    companySize: z.string().min(1, { message: "Please select a company size." }),
    message: z.string().min(10, { message: "Message must be at least 10 characters." }),
});

export default function ContactPage() {
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [submitStatus, setSubmitStatus] = useState<"idle" | "success" | "error">("idle");

    const form = useForm<z.infer<typeof formSchema>>({
        resolver: zodResolver(formSchema),
        defaultValues: {
            name: "",
            email: "",
            companySize: "",
            message: "",
        },
    });

    async function onSubmit(values: z.infer<typeof formSchema>) {
        setIsSubmitting(true);
        setSubmitStatus("idle");

        try {
            const response = await fetch("/api/contact", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(values),
            });

            if (!response.ok) throw new Error("Submission failed");

            setSubmitStatus("success");
            form.reset();
        } catch (error) {
            console.error("Form error:", error);
            setSubmitStatus("error");
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <div className="flex flex-col w-full overflow-hidden bg-brand-surface min-h-screen pb-24">
            {/* Header */}
            <div className="bg-brand-secondary text-white pt-24 pb-32">
                <div className="container max-w-6xl mx-auto px-4 text-center">
                    <h1 className="text-4xl md:text-6xl font-bold tracking-tight mb-6">Let's talk responsible AI.</h1>
                    <p className="text-lg md:text-xl text-white/70 max-w-2xl mx-auto">
                        Reach out for Enterprise inquiries, partnerships, or press. Our team is ready to help you navigate the future of marketing compliance.
                    </p>
                </div>
            </div>

            <div className="container max-w-6xl mx-auto px-4 -mt-16 relative z-10">
                <div className="flex flex-col lg:flex-row gap-8 lg:gap-16">

                    {/* Left Column: Info & Offices */}
                    <div className="flex-1 space-y-8">
                        <div className="bg-white p-8 rounded-2xl shadow-sm border border-brand-border">
                            <h3 className="text-2xl font-bold text-brand-secondary mb-6">Direct Contact</h3>
                            <div className="space-y-6">
                                <div className="flex items-start gap-4">
                                    <div className="p-3 bg-brand-primary/10 rounded-lg text-brand-primary">
                                        <Mail size={24} />
                                    </div>
                                    <div>
                                        <h4 className="font-semibold text-brand-secondary">Sales & Enterprise</h4>
                                        <p className="text-brand-text-secondary text-sm">sales@odradekai.com</p>
                                    </div>
                                </div>
                                <div className="flex items-start gap-4">
                                    <div className="p-3 bg-brand-accent/10 rounded-lg text-brand-accent">
                                        <ShieldCheck size={24} />
                                    </div>
                                    <div>
                                        <h4 className="font-semibold text-brand-secondary">Data Protection Officer (DPO)</h4>
                                        <p className="text-brand-text-secondary text-sm">privacy@odradekai.com</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white p-8 rounded-2xl shadow-sm border border-brand-border">
                            <h3 className="text-2xl font-bold text-brand-secondary mb-6">Our Offices</h3>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                {/* Berlin */}
                                <div>
                                    <div className="flex items-center gap-2 font-bold text-brand-secondary mb-2">
                                        <MapPin size={18} className="text-brand-primary" /> Berlin, DE
                                    </div>
                                    <address className="not-italic text-sm text-brand-text-secondary leading-relaxed">
                                        Rosenthaler Str. 40<br />
                                        10178 Berlin<br />
                                        Germany
                                    </address>
                                </div>
                                {/* Amsterdam */}
                                <div>
                                    <div className="flex items-center gap-2 font-bold text-brand-secondary mb-2">
                                        <MapPin size={18} className="text-brand-accent" /> Amsterdam, NL
                                    </div>
                                    <address className="not-italic text-sm text-brand-text-secondary leading-relaxed">
                                        Vijzelstraat 68<br />
                                        1017 HL Amsterdam<br />
                                        The Netherlands
                                    </address>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Right Column: Form */}
                    <div className="flex-1">
                        <div className="bg-white p-8 md:p-12 rounded-2xl shadow-xl border border-brand-border h-full">
                            <h3 className="text-2xl font-bold text-brand-secondary mb-8">Send a message</h3>

                            {submitStatus === "success" ? (
                                <div className="flex flex-col items-center justify-center h-64 text-center space-y-4">
                                    <div className="w-16 h-16 bg-brand-success/10 rounded-full flex items-center justify-center text-brand-success mb-2">
                                        <ShieldCheck size={32} />
                                    </div>
                                    <h4 className="text-xl font-bold text-brand-secondary">Message Received</h4>
                                    <p className="text-brand-text-secondary">
                                        Thank you for reaching out. Our team will review your inquiry and get back to you within 24 hours.
                                    </p>
                                    <Button
                                        variant="outline"
                                        onClick={() => setSubmitStatus("idle")}
                                        className="mt-4 border-brand-primary text-brand-primary hover:bg-brand-primary/5"
                                    >
                                        Send another message
                                    </Button>
                                </div>
                            ) : (
                                <Form {...form}>
                                    <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
                                        <FormField
                                            control={form.control}
                                            name="name"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel className="text-brand-secondary font-semibold">Full Name</FormLabel>
                                                    <FormControl>
                                                        <Input placeholder="Jane Doe" className="bg-brand-surface border-brand-border focus-visible:ring-brand-primary" {...field} />
                                                    </FormControl>
                                                    <FormMessage className="text-brand-accent text-xs" />
                                                </FormItem>
                                            )}
                                        />

                                        <FormField
                                            control={form.control}
                                            name="email"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel className="text-brand-secondary font-semibold">Work Email</FormLabel>
                                                    <FormControl>
                                                        <Input placeholder="jane@company.com" className="bg-brand-surface border-brand-border focus-visible:ring-brand-primary" {...field} />
                                                    </FormControl>
                                                    <FormMessage className="text-brand-accent text-xs" />
                                                </FormItem>
                                            )}
                                        />

                                        <FormField
                                            control={form.control}
                                            name="companySize"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel className="text-brand-secondary font-semibold">Company Size</FormLabel>
                                                    <div className="grid grid-cols-2 gap-3 mt-2">
                                                        {["1-50", "51-250", "251-1000", "1000+"].map((size) => (
                                                            <div
                                                                key={size}
                                                                onClick={() => field.onChange(size)}
                                                                className={`
                                                px-4 py-2 border rounded-lg text-sm text-center cursor-pointer transition-all
                                                ${field.value === size
                                                                        ? "border-brand-primary bg-brand-primary/5 text-brand-primary font-bold shadow-sm"
                                                                        : "border-brand-border bg-white text-brand-text-secondary hover:border-brand-primary/50"
                                                                    }
                                             `}
                                                            >
                                                                {size} employees
                                                            </div>
                                                        ))}
                                                    </div>
                                                    {/* Invisible input to hold value for form validation */}
                                                    <input type="hidden" {...field} />
                                                    <FormMessage className="text-brand-accent text-xs" />
                                                </FormItem>
                                            )}
                                        />

                                        <FormField
                                            control={form.control}
                                            name="message"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel className="text-brand-secondary font-semibold">How can we help?</FormLabel>
                                                    <FormControl>
                                                        <Textarea
                                                            placeholder="Tell us about your current marketing stack and compliance needs..."
                                                            className="bg-brand-surface border-brand-border focus-visible:ring-brand-primary min-h-[120px] resize-y"
                                                            {...field}
                                                        />
                                                    </FormControl>
                                                    <FormMessage className="text-brand-accent text-xs" />
                                                </FormItem>
                                            )}
                                        />

                                        {submitStatus === "error" && (
                                            <div className="p-3 bg-brand-accent/10 border border-brand-accent/20 rounded-md text-brand-accent text-sm">
                                                Something went wrong. Please try again later.
                                            </div>
                                        )}

                                        <Button
                                            type="submit"
                                            className="w-full bg-brand-primary hover:bg-brand-primary-dark text-white font-bold py-6 shadow-md"
                                            disabled={isSubmitting}
                                        >
                                            {isSubmitting ? "Sending..." : "Submit Inquiry"}
                                        </Button>
                                    </form>
                                </Form>
                            )}

                            <p className="text-xs text-brand-text-secondary mt-6 text-center">
                                By submitting this form, you acknowledge that you have read our <a href="#" className="underline hover:text-brand-primary">Privacy Policy</a>. We do not sell your data.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
