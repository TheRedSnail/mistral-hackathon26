import type { Metadata } from "next";
import { Inter, JetBrains_Mono } from "next/font/google";
import "./globals.css";
import { Navbar } from "@/components/layout/Navbar";
import { Footer } from "@/components/layout/Footer";

const inter = Inter({
  variable: "--font-inter",
  subsets: ["latin"],
});

const jetbrainsMono = JetBrains_Mono({
  variable: "--font-jetbrains-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: {
    default: "ODRADEK | The Guardian Engine for Responsible Marketing",
    template: "%s | ODRADEK",
  },
  description: "AI-Powered · Privacy-First · Ethics-Embedded marketing platform.",
  keywords: ["Responsible AI", "Marketing Compliance", "EU AI Act", "Journey Orchestration", "Voice of Customer", "Ethics By Design", "Dark Pattern Detection"],
  authors: [{ name: "ODRADEK Team" }],
  creator: "ODRADEK",
  openGraph: {
    type: "website",
    locale: "en_IE",
    url: "https://odradekai.com",
    title: "ODRADEK | The Guardian Engine for Responsible Marketing",
    description: "AI-Powered · Privacy-First · Ethics-Embedded. Built for EU AI Act compliance.",
    siteName: "ODRADEK",
    images: [{ url: "https://odradekai.com/og-image.jpg", width: 1200, height: 630, alt: "ODRADEK Marketing Platform" }],
  },
  twitter: {
    card: "summary_large_image",
    title: "ODRADEK | Responsible AI Marketing",
    description: "AI-Powered · Privacy-First · Ethics-Embedded.",
    creator: "@odradek_ai",
  },
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en">
      <body
        className={`${inter.variable} ${jetbrainsMono.variable} antialiased font-sans flex flex-col min-h-screen`}
      >
        <Navbar />
        <main className="flex-1 shrink-0">{children}</main>
        <Footer />
      </body>
    </html>
  );
}
