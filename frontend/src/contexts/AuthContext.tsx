import { createContext, useContext, useEffect, useState, ReactNode } from "react";
import { api } from "@/lib/api";

type AuthContextType = {
  user: any;
  loading: boolean;
  signUp: (email: string, password: string, name: string) => Promise<{ error: any }>;
  signIn: (email: string, password: string) => Promise<{ error: any }>;
  signOut: () => Promise<void>;
};

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider = ({ children }: { children: ReactNode }) => {
  const [user, setUser] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('token');
    if (token) {
      api.get('/me').then(data => {
        setUser(data);
        setLoading(false);
      }).catch(() => {
        setUser(null);
        setLoading(false);
      });
    } else {
      setLoading(false);
    }
  }, []);

  const signIn = async (email: string, password: string) => {
    try {
      const data = await api.post('/login', { email, password });
      localStorage.setItem('token', data.token);
      setUser(data.user);
      return { error: null };
    } catch (err) {
      return { error: err };
    }
  };

  const signUp = async (email: string, password: string, name: string) => {
    try {
      const data = await api.post('/register', { name, email, password });
      localStorage.setItem('token', data.token);
      setUser(data.user);
      return { error: null };
    } catch (err) {
      return { error: err };
    }
  };

  const signOut = async () => {
    await api.post('/logout', {});
    localStorage.removeItem('token');
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{ user, loading, signUp, signIn, signOut }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) throw new Error("useAuth must be used within AuthProvider");
  return context;
};