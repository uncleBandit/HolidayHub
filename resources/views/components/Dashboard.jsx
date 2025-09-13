import React, { useEffect, useState } from 'react';
import { fetchDashboardData } from '../api/dashboard';
import FeaturedSection from '../components/ui/FeaturedSection';
import DestinationCard from '../components/cards/DestinationCard';
import HotelCard from '../components/cards/HotelCard';
import ActivityCard from '../components/cards/ActivityCard';
import OfferCard from '../components/cards/OfferCard';
import PackageCard from '../components/cards/PackageCard';

const Dashboard = () => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchDashboardData()
      .then(responseData => {
        setData(responseData.data);
      })
      .catch(err => {
        setError("Failed to fetch dashboard data. Please try again later.");
      })
      .finally(() => setLoading(false));
  }, []);

  if (loading) {
    return <p className="text-center py-20 text-gray-600">Loading your next adventure...</p>;
  }

  if (error) {
    return <p className="text-center py-20 text-red-500">{error}</p>;
  }

  if (!data) {
    return <p className="text-center py-20 text-gray-500">No content available at the moment.</p>;
  }

  return (
    <div className="container mx-auto px-4 py-12 space-y-16">
      <h1 className="text-4xl md:text-5xl font-extrabold text-center text-primary-600 mb-12 animate-fadeInUp">
        Discover Your Next Journey
      </h1>

      {/* Featured Destinations Section */}
      <FeaturedSection title="Top Destinations" items={data.featured_destinations}>
        {data.featured_destinations.map(dest => (
          <DestinationCard key={dest.id} destination={dest} />
        ))}
      </FeaturedSection>

      {/* Featured Hotels Section */}
      <FeaturedSection title="Luxury Stays" items={data.featured_hotels}>
        {data.featured_hotels.map(hotel => (
          <HotelCard key={hotel.id} hotel={hotel} />
        ))}
      </FeaturedSection>

      {/* Featured Activities Section */}
      <FeaturedSection title="Exciting Activities" items={data.featured_activities}>
        {data.featured_activities.map(activity => (
          <ActivityCard key={activity.id} activity={activity} />
        ))}
      </FeaturedSection>

      {/* Featured Offers Section */}
      <FeaturedSection title="Exclusive Offers" items={data.featured_offers}>
        {data.featured_offers.map(offer => (
          <OfferCard key={offer.id} offer={offer} />
        ))}
      </FeaturedSection>

      {/* Featured Packages Section */}
      <FeaturedSection title="Curated Packages" items={data.featured_packages}>
        {data.featured_packages.map(pkg => (
          <PackageCard key={pkg.id} pkg={pkg} />
        ))}
      </FeaturedSection>
    </div>
  );
};

export default Dashboard;
