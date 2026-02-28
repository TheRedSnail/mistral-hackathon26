import { NextResponse } from "next/server";
import { z } from "zod";

// Same schema as the frontend
const contactSchema = z.object({
    name: z.string().min(2, "Name must be at least 2 characters"),
    email: z.string().email("Invalid email address"),
    companySize: z.string().min(1, "Please select a company size"),
    message: z.string().min(10, "Message must be at least 10 characters"),
});

export async function POST(request: Request) {
    try {
        const body = await request.json();

        // Validate request body
        const validatedData = contactSchema.parse(body);

        // IN A REAL APP: Send email via Resend, SendGrid, or save to DB (Supabase/Prisma)
        // For this prototype, we just mock a successful submission
        console.log("New Contact Submission:", validatedData);

        // Simulate network delay
        await new Promise((resolve) => setTimeout(resolve, 1000));

        return NextResponse.json(
            { message: "Thank you for reaching out. Our team will be in touch shortly." },
            { status: 200 }
        );
    } catch (error) {
        if (error instanceof z.ZodError) {
            const zodError = error as any;
            return NextResponse.json(
                { message: "Validation failed", errors: zodError.errors },
                { status: 400 }
            );
        }

        console.error("Contact API error:", error);
        return NextResponse.json(
            { message: "Internal server error. Please try again later." },
            { status: 500 }
        );
    }
}
