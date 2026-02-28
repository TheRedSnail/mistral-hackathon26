import { Hero } from "@/components/home/Hero";
import { ProblemSection } from "@/components/home/ProblemSection";
import { SolutionSection } from "@/components/home/SolutionSection";
import { FeaturesSection } from "@/components/home/FeaturesSection";
import { ComplianceSection } from "@/components/home/ComplianceSection";
import { PricingSection } from "@/components/home/PricingSection";
import { SocialProofSection } from "@/components/home/SocialProofSection";
import { CTASection } from "@/components/home/CTASection";

export default function Home() {
  return (
    <div className="flex flex-col w-full overflow-hidden">
      <Hero />
      <ProblemSection />
      <SolutionSection />
      <FeaturesSection />
      <ComplianceSection />
      <PricingSection />
      <SocialProofSection />
      <CTASection />
    </div>
  );
}
