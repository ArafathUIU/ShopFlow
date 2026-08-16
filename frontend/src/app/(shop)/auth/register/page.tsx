'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { authService } from '@/lib/services/auth-service';
import { useAuthStore } from '@/lib/stores/auth-store';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Loader2 } from 'lucide-react';

export default function RegisterPage() {
  const router = useRouter();
  const { isAuthenticated, setUser } = useAuthStore();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    if (isAuthenticated) {
      router.push('/');
    }
  }, [isAuthenticated, router]);

  if (isAuthenticated) {
    return null;
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (password !== passwordConfirmation) {
      setError('Passwords do not match');
      return;
    }
    setLoading(true);
    setError('');

    try {
      const data = await authService.register({
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
      });
      if (data) {
        setUser(data.user, data.token);
        router.push('/');
      } else {
        setError('Registration failed');
      }
    } catch {
      setError('Registration failed. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="container mx-auto px-4 sm:px-6 py-12 md:py-20">
      <div className="max-w-md mx-auto">
        <div className="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/50 p-8 md:p-10">
          <div className="text-center mb-8">
            <div className="flex items-center justify-center gap-2 mb-4">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-white font-bold">
                S
              </div>
              <span className="text-xl font-bold text-slate-900">ShopFlow</span>
            </div>
            <h1 className="text-2xl font-bold text-slate-900">Create an account</h1>
            <p className="text-slate-500 mt-2">Join ShopFlow today</p>
          </div>
          <form onSubmit={handleSubmit} className="space-y-5">
            {error && (
              <div className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                {error}
              </div>
            )}
            <div className="space-y-2">
              <Label htmlFor="name" className="text-sm font-medium text-slate-700">Full Name</Label>
              <Input
                id="name"
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
                required
                placeholder="John Doe"
                className="rounded-lg"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="email" className="text-sm font-medium text-slate-700">Email</Label>
              <Input
                id="email"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
                placeholder="you@example.com"
                className="rounded-lg"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="password" className="text-sm font-medium text-slate-700">Password</Label>
              <Input
                id="password"
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
                placeholder="••••••••"
                className="rounded-lg"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="password_confirmation" className="text-sm font-medium text-slate-700">Confirm Password</Label>
              <Input
                id="password_confirmation"
                type="password"
                value={passwordConfirmation}
                onChange={(e) => setPasswordConfirmation(e.target.value)}
                required
                placeholder="••••••••"
                className="rounded-lg"
              />
            </div>
            <Button type="submit" className="w-full rounded-xl shadow-lg shadow-indigo-600/20" disabled={loading}>
              {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              Register
            </Button>
          </form>
          <div className="mt-6 pt-6 border-t border-slate-200">
            <div className="relative">
              <div className="absolute inset-0 flex items-center">
                <div className="w-full border-t border-slate-200" />
              </div>
              <div className="relative flex justify-center text-sm">
                <span className="px-2 bg-white text-slate-500">Or continue with</span>
              </div>
            </div>
            <div className="mt-4 grid grid-cols-2 gap-3">
              <Button variant="outline" className="rounded-lg border-slate-200 hover:bg-slate-50">
                Google
              </Button>
              <Button variant="outline" className="rounded-lg border-slate-200 hover:bg-slate-50">
                GitHub
              </Button>
            </div>
          </div>
          <p className="text-center text-sm text-slate-500 mt-6">
            Already have an account?{' '}
            <Link href="/auth/login" className="text-indigo-600 font-medium hover:text-indigo-700 hover:underline">
              Sign in
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
}
