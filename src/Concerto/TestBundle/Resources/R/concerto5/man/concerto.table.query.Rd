\name{concerto.table.query}
\alias{concerto.table.query}
\title{
Table query
}
\description{
    Perform query on Concerto table.
}
\usage{
    concerto.table.query(sql, params, n, connection)
}
\arguments{
    \item{sql}{
        SQL query to perform.
    }
    \item{params}{
        Named list with parameters.
    }
    \item{n}{
        Maximum number of records to retrieve per fetch.
    }
    \item{connection}{
        Connection to use.
    }
    \item{commit}{
        TRUE if transactions should be automatically committed.
    }
}
\details{
    Contains extended function wizard with SQL query builder when used in Concerto platform.
}
\value{
    Returns result set.
}
\references{
}
\author{
    Przemyslaw Lis
}
\note{
}

\seealso{
}
\examples{
}

\keyword{table}
\keyword{database}