export default function InstallationPage() {
    return (
        <div className="max-w-4xl text-brand-text-primary">
            <div>
                <span className="text-brand-primary font-semibold text-sm tracking-wide uppercase">Getting Started</span>
                <h1 className="text-3xl md:text-5xl font-bold text-brand-secondary mt-2 mb-6 tracking-tight">Installation</h1>
                <p className="text-lg text-brand-text-secondary mb-10 leading-relaxed">
                    These instructions detail how to self-host the open-core version of ODRADEK using Docker. For cloud deployments, refer to the Production guide.
                </p>
            </div>

            <div className="space-y-12">
                <section>
                    <h2 className="text-2xl font-bold text-brand-secondary mb-6 pb-2 border-b border-brand-border">Prerequisites</h2>
                    <ul className="list-disc pl-6 space-y-2 text-brand-text-secondary">
                        <li>Docker and Docker Compose installed (v2.0.0+)</li>
                        <li>Git installed locally</li>
                        <li>Node.js 18+ (if developing the frontend components locally)</li>
                    </ul>
                </section>

                <section>
                    <h2 className="text-2xl font-bold text-brand-secondary mb-6 pb-2 border-b border-brand-border">Setup Guide</h2>

                    <div className="space-y-6">
                        <div>
                            <h3 className="text-lg font-bold mb-3 flex items-center gap-2">
                                <span className="w-6 h-6 rounded-full bg-brand-primary text-white flex items-center justify-center text-sm">1</span>
                                Clone the repository
                            </h3>
                            <div className="block bg-[#0D1F30] rounded-xl p-4 overflow-x-auto text-sm font-mono text-gray-300 border border-brand-secondary/20 shadow-inner">
                                <pre><code><span className="text-gray-500"># Clone the repository</span>
                                    <span className="text-green-400">git</span> clone https://github.com/odradekai/odradek.git
                                    <span className="text-green-400">cd</span> odradek/app</code></pre>
                            </div>
                        </div>

                        <div>
                            <h3 className="text-lg font-bold mb-3 flex items-center gap-2">
                                <span className="w-6 h-6 rounded-full bg-brand-primary text-white flex items-center justify-center text-sm">2</span>
                                Copy environment template
                            </h3>
                            <div className="block bg-[#0D1F30] rounded-xl p-4 overflow-x-auto text-sm font-mono text-gray-300 border border-brand-secondary/20 shadow-inner">
                                <pre><code><span className="text-gray-500"># Copy environment template</span>
                                    <span className="text-green-400">cp</span> .env.example .env

                                    <span className="text-gray-500"># Edit .env with your API keys (OPENAI_API_KEY or ANTHROPIC_API_KEY, </span>
                                    <span className="text-gray-500"># SUPABASE_URL, SUPABASE_ANON_KEY)</span></code></pre>
                            </div>
                        </div>

                        <div>
                            <h3 className="text-lg font-bold mb-3 flex items-center gap-2">
                                <span className="w-6 h-6 rounded-full bg-brand-primary text-white flex items-center justify-center text-sm">3</span>
                                Start the full stack
                            </h3>
                            <div className="block bg-[#0D1F30] rounded-xl p-4 overflow-x-auto text-sm font-mono text-gray-300 border border-brand-secondary/20 shadow-inner">
                                <pre><code><span className="text-gray-500"># Start backend + frontend + PostgreSQL + Redis</span>
                                    <span className="text-green-400">docker compose</span> up -d --build

                                    <span className="text-gray-500"># Backend runs at:  http://localhost:8000</span>
                                    <span className="text-gray-500"># Frontend runs at: http://localhost:3000</span>
                                    <span className="text-gray-500"># API Docs:         http://localhost:8000/docs</span></code></pre>
                            </div>
                        </div>
                    </div>

                    <div className="mt-8 bg-brand-surface border border-brand-border rounded-xl p-6">
                        <h4 className="font-bold text-brand-secondary mb-3">Development mode (hot-reload):</h4>
                        <div className="block bg-[#0D1F30] rounded-xl p-4 overflow-x-auto text-sm font-mono text-gray-300 border border-brand-secondary/20 shadow-inner">
                            <pre><code><span className="text-green-400">make</span> dev
                                <span className="text-gray-500"># or</span>
                                <span className="text-green-400">docker compose</span> -f docker-compose.dev.yml up -d</code></pre>
                        </div>
                    </div>
                </section>

                <section>
                    <h2 className="text-2xl font-bold text-brand-secondary mb-6 pb-2 border-b border-brand-border">Environment Variables</h2>
                    <div className="overflow-x-auto rounded-xl border border-brand-border">
                        <table className="w-full text-left border-collapse text-sm">
                            <thead className="bg-brand-surface text-brand-secondary">
                                <tr>
                                    <th className="py-3 px-4 font-semibold border-b border-brand-border w-1/4">Variable</th>
                                    <th className="py-3 px-4 font-semibold border-b border-border-border w-[10%]">Required</th>
                                    <th className="py-3 px-4 font-semibold border-b border-brand-border w-1/4">Default</th>
                                    <th className="py-3 px-4 font-semibold border-b border-brand-border">Description</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-brand-border">
                                <tr className="hover:bg-brand-surface/50">
                                    <td className="py-3 px-4 font-mono font-medium text-brand-secondary">DATABASE_URL</td>
                                    <td className="py-3 px-4 text-brand-accent">Yes</td>
                                    <td className="py-3 px-4 font-mono text-xs">sqlite+aiosqlite:///./odradek.db</td>
                                    <td className="py-3 px-4 text-brand-text-secondary">Database connection string</td>
                                </tr>
                                <tr className="hover:bg-brand-surface/50">
                                    <td className="py-3 px-4 font-mono font-medium text-brand-secondary">SUPABASE_URL</td>
                                    <td className="py-3 px-4 text-brand-accent">Yes</td>
                                    <td className="py-3 px-4 text-brand-text-secondary">—</td>
                                    <td className="py-3 px-4 text-brand-text-secondary">Your Supabase project URL</td>
                                </tr>
                                <tr className="hover:bg-brand-surface/50">
                                    <td className="py-3 px-4 font-mono font-medium text-brand-secondary">SUPABASE_ANON_KEY</td>
                                    <td className="py-3 px-4 text-brand-accent">Yes</td>
                                    <td className="py-3 px-4 text-brand-text-secondary">—</td>
                                    <td className="py-3 px-4 text-brand-text-secondary">Supabase anonymous key</td>
                                </tr>
                                <tr className="hover:bg-brand-surface/50">
                                    <td className="py-3 px-4 font-mono font-medium text-brand-secondary">OPENAI_API_KEY</td>
                                    <td className="py-3 px-4 text-brand-warning">One required</td>
                                    <td className="py-3 px-4 text-brand-text-secondary">—</td>
                                    <td className="py-3 px-4 text-brand-text-secondary">OpenAI API key (GPT-4o)</td>
                                </tr>
                                <tr className="hover:bg-brand-surface/50">
                                    <td className="py-3 px-4 font-mono font-medium text-brand-secondary">ANTHROPIC_API_KEY</td>
                                    <td className="py-3 px-4 text-brand-warning">One required</td>
                                    <td className="py-3 px-4 text-brand-text-secondary">—</td>
                                    <td className="py-3 px-4 text-brand-text-secondary">Anthropic API key (Claude)</td>
                                </tr>
                                <tr className="hover:bg-brand-surface/50">
                                    <td className="py-3 px-4 font-mono font-medium text-brand-secondary">ENVIRONMENT</td>
                                    <td className="py-3 px-4 text-brand-text-secondary">No</td>
                                    <td className="py-3 px-4 font-mono text-xs">development</td>
                                    <td className="py-3 px-4 text-brand-text-secondary">development | staging | production</td>
                                </tr>
                                <tr className="hover:bg-brand-surface/50">
                                    <td className="py-3 px-4 font-mono font-medium text-brand-secondary">CORS_ORIGINS</td>
                                    <td className="py-3 px-4 text-brand-primary">Production</td>
                                    <td className="py-3 px-4 text-brand-text-secondary">—</td>
                                    <td className="py-3 px-4 text-brand-text-secondary">Comma-separated allowed origins</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section>
                    <h2 className="text-2xl font-bold text-brand-secondary mb-6 pb-2 border-b border-brand-border">Quick Start</h2>
                    <ol className="list-decimal pl-6 space-y-3 text-brand-text-primary">
                        <li>Navigate to <span className="font-mono text-sm bg-brand-surface px-1.5 py-0.5 rounded border border-brand-border">http://localhost:3000</span></li>
                        <li>Register your organization</li>
                        <li>Import sample feedback data via <strong>Settings → Data Management → Import CSV</strong></li>
                        <li>Run your first sentiment analysis in VoC Analytics</li>
                        <li>Enable the Guardian Engine and watch your Ethics Score appear</li>
                    </ol>
                </section>

                <section>
                    <h2 className="text-2xl font-bold text-brand-secondary mb-6 pb-2 border-b border-brand-border">Makefile Commands</h2>
                    <div className="overflow-x-auto rounded-xl border border-brand-border">
                        <table className="w-full text-left border-collapse text-sm">
                            <thead className="bg-brand-surface text-brand-secondary">
                                <tr>
                                    <th className="py-3 px-4 font-semibold border-b border-brand-border w-1/3">Command</th>
                                    <th className="py-3 px-4 font-semibold border-b border-brand-border">Description</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-brand-border">
                                <tr className="hover:bg-brand-surface/50">
                                    <td className="py-3 px-4 font-mono font-medium text-brand-primary">make dev</td>
                                    <td className="py-3 px-4 text-brand-text-secondary">Start backend + frontend in development mode</td>
                                </tr>
                                <tr className="hover:bg-brand-surface/50">
                                    <td className="py-3 px-4 font-mono font-medium text-brand-primary">make up-prod</td>
                                    <td className="py-3 px-4 text-brand-text-secondary">Start in production mode</td>
                                </tr>
                                <tr className="hover:bg-brand-surface/50">
                                    <td className="py-3 px-4 font-mono font-medium text-brand-primary">make test</td>
                                    <td className="py-3 px-4 text-brand-text-secondary">Run backend test suite</td>
                                </tr>
                                <tr className="hover:bg-brand-surface/50">
                                    <td className="py-3 px-4 font-mono font-medium text-brand-primary">make test-cov</td>
                                    <td className="py-3 px-4 text-brand-text-secondary">Tests with coverage report</td>
                                </tr>
                                <tr className="hover:bg-brand-surface/50">
                                    <td className="py-3 px-4 font-mono font-medium text-brand-primary">make db-migrate msg="..."</td>
                                    <td className="py-3 px-4 text-brand-text-secondary">Generate new migration</td>
                                </tr>
                                <tr className="hover:bg-brand-surface/50">
                                    <td className="py-3 px-4 font-mono font-medium text-brand-primary">make db-upgrade</td>
                                    <td className="py-3 px-4 text-brand-text-secondary">Apply pending migrations</td>
                                </tr>
                                <tr className="hover:bg-brand-surface/50">
                                    <td className="py-3 px-4 font-mono font-medium text-brand-primary">make logs</td>
                                    <td className="py-3 px-4 text-brand-text-secondary">Tail all container logs</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    );
}
