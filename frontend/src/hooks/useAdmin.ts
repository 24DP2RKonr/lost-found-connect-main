import { useState, useEffect } from "react";
import { useAuth } from "@/contexts/AuthContext";

export function useAdmin(): { isAdmin: boolean; loading: boolean } {
  const { user } = useAuth();
  const [isAdmin, setIsAdmin] = useState(false);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!user) {
      setIsAdmin(false);
      setLoading(false);
      return;
    }

    setIsAdmin(Boolean((user as any).is_admin));
    setLoading(false);
  }, [user]);

  return { isAdmin, loading };
}
