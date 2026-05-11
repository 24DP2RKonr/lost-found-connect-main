import * as React from "react";
import { api } from "@/lib/api";

export interface Listing {
  id: string;
  title: string;
  description: string;
  type: "lost" | "found";
  category: string;
  location: string;
  date: string;
  image: string;
  views: number;
  userId?: string;
  created_at?: string;
}

// Category mapping
const categoryMap: Record<string, string> = {
  electronics: "Elektronika",
  documents: "Dokumenti",
  keys: "Atslēgas",
  wallet: "Maks / Somas",
  clothing: "Apģērbs",
  jewelry: "Rotaslietas",
  pets: "Mājdzīvnieki",
  other: "Cits",
};

function mapRow(row: any): Listing {
  return {
    id: String(row.id),
    title: row.title,
    description: row.description,
    type: row.type as "lost" | "found",
    category: row.category,
    location: row.location,
    date: row.date,
    image: row.image,
    views: row.views,
    userId: row.user_id,
    created_at: row.created_at,
  };
}

let listings: Listing[] = [];
let listeners: (() => void)[] = [];
let loaded = false;

function notifyListeners() {
  listeners.forEach((l) => l());
}

async function loadFromDb() {
  try {
    const data = await api.get("/listings");
    listings = Array.isArray(data) ? data.map(mapRow) : [];
    loaded = true;
    notifyListeners();
  } catch (e) {
    console.error("Failed to load listings", e);
  }
}

// Initial load
loadFromDb();

export const listingsStore = {
  getListings: (): Listing[] => listings,

  getListing: (id: string): Listing | undefined => {
    return listings.find((l) => l.id === id);
  },

  reload: loadFromDb,

  addListing: async (data: {
    title: string;
    description: string;
    category: string;
    location: string;
    date: string;
    type: "lost" | "found";
    imageFile?: File | null;
    images: string[];
    userId?: string;
  }): Promise<Listing | null> => {
    const formData = new FormData();
    formData.append("title", data.title);
    formData.append("description", data.description);
    formData.append("type", data.type);
    formData.append("category", categoryMap[data.category] || data.category);
    formData.append("location", data.location);
    formData.append("date", data.date || new Date().toISOString().split("T")[0]);

    if (data.imageFile) {
      formData.append("image_file", data.imageFile);
    } else {
      formData.append(
        "image",
        data.images[0] ||
          "https://images.unsplash.com/photo-1586769852044-692d6e3703f0?w=400&q=80"
      );
    }

    let inserted: any;
    try {
      inserted = await api.post("/listings", formData);
    } catch (e) {
      console.error("Failed to insert listing", e);
      return null;
    }

    const newListing = mapRow(inserted);
    listings = [newListing, ...listings];
    notifyListeners();
    return newListing;
  },

  deleteListing: async (id: string): Promise<void> => {
    try {
      await api.delete(`/listings/${id}`);
      listings = listings.filter((l) => l.id !== id);
      notifyListeners();
    } catch (e) {
      console.error("Failed to delete listing", e);
    }
  },

  incrementViews: async (id: string): Promise<void> => {
    const listing = listings.find((l) => l.id === id);
    if (!listing) return;
    const newViews = listing.views + 1;
    // Optimistic update
    listings = listings.map((l) => (l.id === id ? { ...l, views: newViews } : l));
    notifyListeners();
    try {
      await api.put(`/listings/${id}`, { views: newViews });
    } catch (e) {
      console.error("Failed to update views", e);
    }
  },

  subscribe: (listener: () => void): (() => void) => {
    listeners.push(listener);
    return () => {
      listeners = listeners.filter((l) => l !== listener);
    };
  },
};

export function useListings(): Listing[] {
  const [, forceUpdate] = React.useState({});

  React.useEffect(() => {
    if (!loaded) loadFromDb();
    return listingsStore.subscribe(() => forceUpdate({}));
  }, []);

  return listingsStore.getListings();
}
